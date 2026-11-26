<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\Organization\InvitationService;
use Illuminate\Console\Command;

class BootstrapSuperAdminCommand extends Command
{
    protected $signature = 'blf:bootstrap-admin {email : Verified Google email address of the initial Platform Super Administrator}';

    protected $description = 'Create a single-use invitation for a Platform Super Administrator';

    public function handle(InvitationService $invitationService): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        $role = Role::query()->where('key', 'platform_super_admin')->first();

        if (! $role instanceof Role) {
            $this->error('The platform_super_admin role was not found. Run database seeders first.');

            return self::FAILURE;
        }

        $result = $invitationService->createInvitation(
            actor: null,
            email: $email,
            role: $role,
            organization: null,
            expiresInDays: 7,
        );

        $url = route('invitations.accept', ['token' => $result['plain_token']]);

        $this->info('Platform Super Administrator invitation created.');
        $this->line('Email: '.$email);
        $this->line('Expires: '.$result['invitation']->expires_at->toDateTimeString());
        $this->line('Accept URL: '.$url);

        return self::SUCCESS;
    }
}
