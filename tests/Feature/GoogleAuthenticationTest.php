<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_google_sign_in_without_password_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Continue with Google');
        $response->assertDontSee('type="password"', false);
    }

    public function test_redirect_returns_error_when_google_oauth_is_not_configured(): void
    {
        config()->set('services.google.client_id', '');
        config()->set('services.google.client_secret', '');

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
    }

    public function test_redirect_requests_minimal_scopes_and_stores_invite_token(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('setScopes')
            ->once()
            ->with(['openid', 'email', 'profile'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this->get(route('auth.google.redirect', ['invite' => 'invite-token-123']));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $response->assertSessionHas('oauth_invite_token', 'invite-token-123');
    }

    public function test_callback_registers_new_public_user_and_regenerates_session(): void
    {
        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-1001',
            email: 'tourist@example.com',
            name: 'Maria Santos',
            avatar: 'https://example.com/avatar.jpg',
        ));

        $oldSessionId = session()->getId();

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertNotSame($oldSessionId, session()->getId());

        $user = User::query()->where('google_id', 'google-sub-1001')->firstOrFail();
        $this->assertSame('tourist@example.com', $user->email);
        $this->assertSame('Maria Santos', $user->name);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);

        $this->assertTrue(AuditLog::query()->where('user_id', $user->id)->where('action', 'auth.registered_via_google')->exists());
        $this->assertTrue(AuditLog::query()->where('user_id', $user->id)->where('action', 'auth.login')->exists());
    }

    public function test_callback_logs_in_existing_user_by_google_id_and_audits_email_update(): void
    {
        $existing = User::factory()->create([
            'google_id' => 'google-sub-2002',
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-2002',
            email: 'new@example.com',
            name: 'Updated Name',
        ));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($existing);

        $existing->refresh();
        $this->assertSame('new@example.com', $existing->email);
        $this->assertSame('Updated Name', $existing->name);
        $this->assertSame(1, User::query()->count());
        $this->assertTrue(AuditLog::query()->where('user_id', $existing->id)->where('action', 'auth.google_email_updated')->exists());
    }

    public function test_callback_links_preprovisioned_account_without_google_id(): void
    {
        $preprovisioned = User::factory()->withoutGoogleId()->create([
            'email' => 'admin@boracay.gov.ph',
        ]);

        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-3003',
            email: 'admin@boracay.gov.ph',
            name: 'Barangay Admin',
        ));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($preprovisioned);
        $this->assertSame('google-sub-3003', $preprovisioned->fresh()->google_id);
        $this->assertTrue(AuditLog::query()->where('user_id', $preprovisioned->id)->where('action', 'auth.google_linked')->exists());
    }

    public function test_callback_refuses_email_bound_to_different_google_id(): void
    {
        User::factory()->create([
            'google_id' => 'google-sub-original',
            'email' => 'conflict@example.com',
        ]);

        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-different',
            email: 'conflict@example.com',
            name: 'Other Account',
        ));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_callback_refuses_unverified_google_email(): void
    {
        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-unverified',
            email: 'unverified@example.com',
            name: 'Unverified User',
            emailVerified: false,
        ));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_callback_refuses_suspended_user(): void
    {
        User::factory()->suspended()->create([
            'google_id' => 'google-sub-suspended',
            'email' => 'suspended@example.com',
        ]);

        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-suspended',
            email: 'suspended@example.com',
            name: 'Suspended User',
        ));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_callback_handles_cancelled_authentication(): void
    {
        $response = $this->get(route('auth.google.callback', ['error' => 'access_denied']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_callback_handles_invalid_oauth_state(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_callback_handles_provider_failure(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andThrow(new RuntimeException('Token revoked'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_logout_signs_out_user_and_records_audit_log(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('user_id', $user->id)->where('action', 'auth.logout')->exists());
    }

    public function test_configured_super_admin_email_auto_assigns_role_on_fresh_google_login(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        config()->set('auth.auto_role_emails', [
            'platform_super_admin' => ['boracaylostandfoundsystem@gmail.com', 'apiloton1@snsu.edu.ph'],
            'institution_admin' => [],
            'institution_staff' => [],
        ]);

        $this->mockGoogleUser($this->makeSocialiteUser(
            id: 'google-sub-super-auto',
            email: 'boracaylostandfoundsystem@gmail.com',
            name: 'Boracay Super Admin',
        ));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $user = User::query()->where('email', 'boracaylostandfoundsystem@gmail.com')->firstOrFail();
        $this->assertTrue($user->isPlatformSuperAdmin());
    }

    protected function mockGoogleUser(SocialiteUser $socialiteUser): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }

    protected function makeSocialiteUser(
        string $id,
        string $email,
        string $name,
        ?string $avatar = null,
        bool $emailVerified = true,
    ): SocialiteUser {
        $user = new SocialiteUser;
        $user->id = $id;
        $user->email = $email;
        $user->name = $name;
        $user->avatar = $avatar;
        $user->user = [
            'sub' => $id,
            'email' => $email,
            'email_verified' => $emailVerified,
            'name' => $name,
        ];

        return $user;
    }
}
