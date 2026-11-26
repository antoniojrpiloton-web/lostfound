<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'blf:backup-db
        {--prune-days=14 : Delete backup files older than this many days}
        {--binary= : Explicit path to mysqldump binary}';

    protected $description = 'Create a consistent MySQL backup snapshot of the Boracay Lost and Found database.';

    public function handle(): int
    {
        $connection = (string) config('database.default', 'mysql');
        $config = (array) config("database.connections.{$connection}", []);

        if (($config['driver'] ?? '') !== 'mysql') {
            $this->error('blf:backup-db only supports the mysql driver.');

            return self::FAILURE;
        }

        $backupDir = storage_path('app/private/backups');
        File::ensureDirectoryExists($backupDir);

        $database = (string) ($config['database'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $username = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');

        $filename = sprintf('%s_%s.sql', $database, now()->format('Ymd_His'));
        $targetPath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $binary = (string) ($this->option('binary') ?: $this->resolveMysqldumpBinary());

        $command = [
            $binary,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--result-file='.$targetPath,
            $database,
        ];

        $env = [];
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = new Process($command, null, $env, null, 300);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($targetPath)) {
            $this->error('Database backup failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));

            return self::FAILURE;
        }

        $this->info('Database backup created: '.$targetPath.' ('.File::size($targetPath).' bytes)');

        $pruneDays = max(1, (int) $this->option('prune-days'));
        $cutoff = now()->subDays($pruneDays)->getTimestamp();
        $pruned = 0;

        foreach (File::files($backupDir) as $file) {
            if ($file->getExtension() === 'sql' && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $pruned++;
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup file(s) older than {$pruneDays} day(s).");
        }

        return self::SUCCESS;
    }

    protected function resolveMysqldumpBinary(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = glob('C:\\wamp64\\bin\\mysql\\mysql*\\bin\\mysqldump.exe') ?: [];
            if ($candidates !== []) {
                rsort($candidates);

                return $candidates[0];
            }
        }

        return 'mysqldump';
    }
}
