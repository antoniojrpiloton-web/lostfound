<?php

namespace App\Services\Auth;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Exceptions\GoogleAuthenticationException;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAuthService
{
    public function resolveUser(SocialiteUser $googleUser, Request $request): User
    {
        $googleId = (string) $googleUser->getId();
        $email = strtolower(trim((string) $googleUser->getEmail()));
        $name = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: 'Boracay User'));
        $avatar = $googleUser->getAvatar();

        if ($googleId === '' || $email === '') {
            throw new GoogleAuthenticationException(__('Google did not return a valid account identity.'));
        }

        $raw = property_exists($googleUser, 'user') && is_array($googleUser->user) ? $googleUser->user : [];
        if (array_key_exists('email_verified', $raw) && ! (bool) $raw['email_verified']) {
            throw new GoogleAuthenticationException(__('Your Google email address must be verified before signing in.'));
        }

        $googleLocale = $this->resolveSupportedLocale(isset($raw['locale']) ? (string) $raw['locale'] : null);

        return DB::transaction(function () use ($googleId, $email, $name, $avatar, $googleLocale, $request): User {
            $user = User::query()->where('google_id', $googleId)->first();

            if ($user instanceof User) {
                $this->ensureAccountActive($user);

                $oldEmail = $user->email;
                $emailConflict = $oldEmail !== $email
                    && User::query()->where('email', $email)->whereKeyNot($user->id)->exists();

                if ($emailConflict) {
                    throw new GoogleAuthenticationException(__('Account conflict detected. Please contact system support.'));
                }

                $user->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'avatar_url' => $avatar,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'last_login_at' => now(),
                ])->save();

                if ($oldEmail !== $email) {
                    $this->recordAudit($user, 'auth.google_email_updated', $request, ['email' => $oldEmail], ['email' => $email]);
                }

                $this->syncConfiguredRoleMembership($user, $request);
                $this->recordAudit($user, 'auth.login', $request);

                return $user;
            }

            $existingByEmail = User::query()->where('email', $email)->first();

            if ($existingByEmail instanceof User) {
                if ($existingByEmail->google_id !== null) {
                    throw new GoogleAuthenticationException(__('Account conflict detected. Please contact system support.'));
                }

                $this->ensureAccountActive($existingByEmail);

                $existingByEmail->forceFill([
                    'google_id' => $googleId,
                    'name' => $name,
                    'avatar_url' => $avatar,
                    'email_verified_at' => $existingByEmail->email_verified_at ?? now(),
                    'last_login_at' => now(),
                ])->save();

                $this->syncConfiguredRoleMembership($existingByEmail, $request);
                $this->recordAudit($existingByEmail, 'auth.google_linked', $request, ['google_id' => null], ['google_id' => $googleId]);
                $this->recordAudit($existingByEmail, 'auth.login', $request);

                return $existingByEmail;
            }

            $newUser = User::query()->create([
                'google_id' => $googleId,
                'name' => $name,
                'email' => $email,
                'avatar_url' => $avatar,
                'locale' => $googleLocale,
                'status' => UserStatus::Active,
                'last_login_at' => now(),
                'password' => null,
            ]);

            $newUser->forceFill(['email_verified_at' => now()])->save();

            $this->syncConfiguredRoleMembership($newUser, $request);
            $this->recordAudit($newUser, 'auth.registered_via_google', $request);
            $this->recordAudit($newUser, 'auth.login', $request);

            return $newUser;
        });
    }

    public function recordLogout(User $user, Request $request): void
    {
        $this->recordAudit($user, 'auth.logout', $request);
    }

    protected function ensureAccountActive(User $user): void
    {
        if ($user->status === UserStatus::Suspended) {
            throw new GoogleAuthenticationException(__('Your account is currently suspended. Please contact system support.'));
        }
    }

    protected function syncConfiguredRoleMembership(User $user, Request $request): void
    {
        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return;
        }

        $roleMap = (array) config('auth.auto_role_emails', []);
        $matchedRoleKey = null;

        foreach (['platform_super_admin', 'institution_admin', 'institution_staff'] as $roleKey) {
            $emails = array_map(
                fn (mixed $item): string => strtolower(trim((string) $item)),
                (array) ($roleMap[$roleKey] ?? [])
            );

            if (in_array($email, $emails, true)) {
                $matchedRoleKey = $roleKey;
                break;
            }
        }

        if ($matchedRoleKey === null) {
            return;
        }

        $role = Role::query()->where('key', $matchedRoleKey)->first();
        if (! $role instanceof Role) {
            return;
        }

        $organizationId = null;
        if ($matchedRoleKey !== 'platform_super_admin') {
            $defaultCode = (string) config('auth.auto_role_default_org_code', 'PNP-MALAY-BTAC');
            $organizationId = Organization::query()->where('code', $defaultCode)->value('id')
                ?? Organization::query()->where('is_active', true)->orderBy('id')->value('id');
        }

        $existing = OrganizationMembership::query()
            ->where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->first();

        if ($existing instanceof OrganizationMembership) {
            if ($existing->role_id !== $role->id || $existing->status !== MembershipStatus::Active) {
                $existing->forceFill([
                    'role_id' => $role->id,
                    'status' => MembershipStatus::Active,
                    'approved_at' => $existing->approved_at ?? now(),
                ])->save();
            }

            return;
        }

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ]);

        $this->recordAudit($user, 'auth.auto_role_assigned', $request, null, [
            'role_key' => $matchedRoleKey,
            'organization_id' => $organizationId,
        ]);
    }

    protected function resolveSupportedLocale(?string $rawLocale): string
    {
        $supported = array_keys((array) config('localization.supported', ['en' => []]));
        $fallback = in_array(app()->getLocale(), $supported, true)
            ? app()->getLocale()
            : (string) config('localization.default', 'en');

        if ($rawLocale === null || trim($rawLocale) === '') {
            return $fallback;
        }

        $normalized = strtolower(str_replace('_', '-', trim($rawLocale)));
        if (in_array($normalized, $supported, true)) {
            return $normalized;
        }

        $primary = explode('-', $normalized)[0];
        if ($primary === 'tl' && in_array('fil', $supported, true)) {
            return 'fil';
        }

        return in_array($primary, $supported, true) ? $primary : $fallback;
    }

    protected function recordAudit(User $user, string $action, Request $request, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => $action,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
