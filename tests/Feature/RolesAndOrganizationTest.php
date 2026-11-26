<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Organization\InvitationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class RolesAndOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_user_and_guest_cannot_access_admin_routes(): void
    {
        $publicUser = User::factory()->create();

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($publicUser)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($publicUser)->get(route('admin.organizations.index'))->assertForbidden();
        $this->actingAs($publicUser)->get(route('admin.memberships.index'))->assertForbidden();
        $this->actingAs($publicUser)->get(route('admin.invitations.index'))->assertForbidden();
    }

    public function test_bootstrap_super_admin_command_creates_valid_invitation(): void
    {
        $this->artisan('blf:bootstrap-admin', ['email' => 'superadmin@boracay.gov.ph'])
            ->expectsOutputToContain('Platform Super Administrator invitation created.')
            ->assertSuccessful();

        $invitation = OrganizationInvitation::query()->where('email', 'superadmin@boracay.gov.ph')->firstOrFail();
        $this->assertNull($invitation->organization_id);
        $this->assertSame('platform_super_admin', $invitation->role->key);
        $this->assertTrue(AuditLog::query()->where('action', 'organization.invitation_created')->exists());
    }

    public function test_google_login_with_valid_invite_activates_institutional_access(): void
    {
        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $adminRole = Role::query()->where('key', 'institution_admin')->firstOrFail();

        $created = app(InvitationService::class)->createInvitation(
            actor: $superAdmin,
            email: 'officer@balabag.gov.ph',
            role: $adminRole,
            organization: $org,
        );

        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = 'google-sub-officer-1';
        $socialiteUser->email = 'officer@balabag.gov.ph';
        $socialiteUser->name = 'Barangay Officer';
        $socialiteUser->user = [
            'sub' => 'google-sub-officer-1',
            'email' => 'officer@balabag.gov.ph',
            'email_verified' => true,
            'name' => 'Barangay Officer',
        ];

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->withSession(['oauth_invite_token' => $created['plain_token']])
            ->get(route('auth.google.callback'));

        $response->assertRedirect(route('admin.dashboard'));

        $officer = User::query()->where('email', 'officer@balabag.gov.ph')->firstOrFail();
        $this->assertTrue($officer->isInstitutionalUser());
        $this->assertSame('institution_admin', $officer->roleKey());
        $this->assertSame($org->id, $officer->organizationId());
        $this->assertNotNull($created['invitation']->fresh()->accepted_at);
    }

    public function test_invitation_cannot_be_accepted_when_expired_revoked_or_email_mismatched(): void
    {
        $superAdmin = $this->createUserWithRole('platform_super_admin');
        $org = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $staffRole = Role::query()->where('key', 'institution_staff')->firstOrFail();
        $service = app(InvitationService::class);

        $wrongEmailUser = User::factory()->create(['email' => 'other@example.com']);
        $invite = $service->createInvitation($superAdmin, 'expected@example.com', $staffRole, $org);

        $this->actingAs($wrongEmailUser)
            ->get(route('invitations.accept', ['token' => $invite['plain_token']]))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('invitation');
        $this->assertFalse($wrongEmailUser->fresh()->isInstitutionalUser());

        $expired = $service->createInvitation($superAdmin, 'expired@example.com', $staffRole, $org);
        $expired['invitation']->forceFill(['expires_at' => now()->subMinute()])->save();
        $expiredUser = User::factory()->create(['email' => 'expired@example.com']);

        $this->actingAs($expiredUser)
            ->get(route('invitations.accept', ['token' => $expired['plain_token']]))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('invitation');

        $revoked = $service->createInvitation($superAdmin, 'revoked@example.com', $staffRole, $org);
        $service->revokeInvitation($revoked['invitation'], $superAdmin);
        $revokedUser = User::factory()->create(['email' => 'revoked@example.com']);

        $this->actingAs($revokedUser)
            ->get(route('invitations.accept', ['token' => $revoked['plain_token']]))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('invitation');
    }

    public function test_platform_super_admin_can_manage_organizations(): void
    {
        $superAdmin = $this->createUserWithRole('platform_super_admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.organizations.store'), [
                'type' => OrganizationType::PoliceStation->value,
                'code' => 'PNP-YAPAK-OUTPOST',
                'name' => 'Yapak Police Outpost',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $org = Organization::query()->where('code', 'PNP-YAPAK-OUTPOST')->firstOrFail();
        $this->assertTrue(AuditLog::query()->where('action', 'organization.created')->where('auditable_id', $org->id)->exists());

        $this->actingAs($superAdmin)
            ->put(route('admin.organizations.update', $org), [
                'type' => OrganizationType::PoliceStation->value,
                'code' => 'PNP-YAPAK-OUTPOST',
                'name' => 'Yapak Police Sub-Station',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertSame('Yapak Police Sub-Station', $org->fresh()->name);
    }

    public function test_institution_admin_can_manage_own_staff_but_not_other_organizations_or_global_orgs(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $yapak = Organization::query()->where('code', 'LGU-YAPAK')->firstOrFail();

        $balabagAdmin = $this->createUserWithRole('institution_admin', $balabag);
        $balabagStaff = $this->createUserWithRole('institution_staff', $balabag);
        $yapakStaff = $this->createUserWithRole('institution_staff', $yapak);

        $this->actingAs($balabagAdmin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($balabagAdmin)->get(route('admin.organizations.index'))->assertForbidden();

        $this->actingAs($balabagAdmin)
            ->post(route('admin.invitations.store'), [
                'email' => 'newstaff@balabag.gov.ph',
                'role_key' => 'institution_staff',
            ])
            ->assertRedirect(route('admin.invitations.index'));

        $this->assertTrue(OrganizationInvitation::query()->where('email', 'newstaff@balabag.gov.ph')->where('organization_id', $balabag->id)->exists());

        $this->actingAs($balabagAdmin)
            ->post(route('admin.invitations.store'), [
                'email' => 'escalate@balabag.gov.ph',
                'role_key' => 'institution_admin',
            ])
            ->assertSessionHasErrors('role_key');

        $balabagStaffMembership = $balabagStaff->memberships()->firstOrFail();
        $yapakStaffMembership = $yapakStaff->memberships()->firstOrFail();

        $this->actingAs($balabagAdmin)
            ->patch(route('admin.memberships.update', $balabagStaffMembership), [
                'status' => MembershipStatus::Suspended->value,
            ])
            ->assertRedirect(route('admin.memberships.index'));

        $this->assertSame(MembershipStatus::Suspended, $balabagStaffMembership->fresh()->status);
        $this->assertSame(UserStatus::Suspended, $balabagStaff->fresh()->status);

        $this->actingAs($balabagStaff->fresh())
            ->get(route('reports.lost.create'))
            ->assertRedirect(route('login'));

        $this->actingAs($balabagAdmin)
            ->patch(route('admin.memberships.update', $yapakStaffMembership), [
                'status' => MembershipStatus::Suspended->value,
            ])
            ->assertForbidden();

        $this->assertSame(MembershipStatus::Active, $yapakStaffMembership->fresh()->status);
    }

    public function test_institution_staff_can_access_dashboard_but_not_admin_management_routes(): void
    {
        $balabag = Organization::query()->where('code', 'LGU-BALABAG')->firstOrFail();
        $staff = $this->createUserWithRole('institution_staff', $balabag);

        $this->actingAs($staff)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($staff)->get(route('admin.organizations.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.memberships.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.invitations.index'))->assertForbidden();
    }

    protected function createUserWithRole(string $roleKey, ?Organization $organization = null): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('key', $roleKey)->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $user->id,
            'organization_id' => $organization?->id,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ]);

        return $user;
    }
}
