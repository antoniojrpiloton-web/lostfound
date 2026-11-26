<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageEmbedding;
use App\Models\ItemImage;
use App\Models\ItemMatch;
use App\Models\ItemReport;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AdminSystemHealthController extends Controller
{
    public function __construct(public AuditService $auditService) {}

    public function index(): View
    {
        $validatorStatus = [
            'enabled' => (bool) config('services.image_validator.enabled', false),
            'url' => (string) config('services.image_validator.url', 'http://127.0.0.1:8001'),
            'online' => false,
            'clip_enabled' => false,
            'service_version' => null,
        ];

        if ($validatorStatus['enabled']) {
            try {
                $response = Http::timeout(2)->get(rtrim($validatorStatus['url'], '/').'/health');
                if ($response->successful()) {
                    $body = $response->json() ?? [];
                    $validatorStatus['online'] = ($body['status'] ?? '') === 'ok';
                    $validatorStatus['clip_enabled'] = (bool) ($body['clip_enabled'] ?? false);
                    $validatorStatus['service_version'] = (string) ($body['service_version'] ?? '1.0.0');
                }
            } catch (Throwable) {
                $validatorStatus['online'] = false;
            }
        }

        $stats = [
            'reports' => ItemReport::query()->count(),
            'images' => ItemImage::query()->count(),
            'embeddings' => ImageEmbedding::query()->count(),
            'matches' => ItemMatch::query()->count(),
            'embedding_models' => ImageEmbedding::query()
                ->selectRaw('model_version, COUNT(*) as total')
                ->groupBy('model_version')
                ->pluck('total', 'model_version')
                ->all(),
        ];

        $backupDir = storage_path('app/private/backups');
        $backups = [];
        if (File::isDirectory($backupDir)) {
            foreach (File::files($backupDir) as $file) {
                if (strtolower($file->getExtension()) !== 'sql') {
                    continue;
                }
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            }
            usort($backups, fn (array $a, array $b): int => $b['modified_at']->getTimestamp() <=> $a['modified_at']->getTimestamp());
        }

        return view('admin.system-health.index', compact('validatorStatus', 'stats', 'backups'));
    }

    public function runBackup(Request $request): RedirectResponse
    {
        $exitCode = Artisan::call('blf:backup-db');
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()
                ->route('admin.system_health.index')
                ->withErrors(['backup' => $output !== '' ? $output : __('Database backup failed.')]);
        }

        $this->auditService->record(
            action: 'system.backup_created',
            auditable: null,
            oldValues: null,
            newValues: ['output' => $output],
            actor: $request->user(),
            request: $request,
        );

        return redirect()
            ->route('admin.system_health.index')
            ->with('status', __('Database backup created successfully.'));
    }

    public function downloadBackup(string $filename): BinaryFileResponse
    {
        if (! preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $filename)) {
            abort(404);
        }

        $path = storage_path('app/private/backups/'.$filename);
        if (! File::isFile($path)) {
            abort(404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }
}
