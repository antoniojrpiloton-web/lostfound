<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_and_help_page_render_with_public_layout(): void
    {
        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('Boracay Lost and Found System');
        $home->assertSee('Sign In with Google');
        $home->assertSee('How It Works');
        $home->assertSee('Report Lost Item');

        $help = $this->get(route('public.help'));
        $help->assertOk();
        $help->assertSee('Privacy Notice (RA 10173)');
    }

    public function test_admin_pages_render_with_gentelella_style_layout_and_components(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdmin = User::factory()->create(['name' => 'Elena Cruz']);
        $role = Role::query()->where('key', 'platform_super_admin')->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $superAdmin->id,
            'organization_id' => null,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
            'approved_at' => now(),
        ]);

        $dashboard = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('data-admin-shell', false);
        $dashboard->assertSee('data-sidebar-toggle', false);
        $dashboard->assertSee('data-global-search', false);
        $dashboard->assertSee('blfConfirmModal', false);
        $dashboard->assertSee('Total Lost Reports');
        $dashboard->assertSee('Recent Activity');

        $this->actingAs($superAdmin)
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertSee('LGU-BALABAG')
            ->assertSee('createOrganizationModal', false);

        $this->actingAs($superAdmin)
            ->get(route('admin.memberships.index'))
            ->assertOk()
            ->assertSee('Elena Cruz');

        $this->actingAs($superAdmin)
            ->get(route('admin.invitations.index'))
            ->assertOk()
            ->assertSee('createInvitationModal', false);
    }
}
