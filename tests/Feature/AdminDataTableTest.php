<?php

namespace Tests\Feature;

use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDataTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_reports_datatable_returns_server_side_json_with_actions_in_first_column(): void
    {
        $org = Organization::query()->firstOrFail();
        $admin = User::factory()->create();
        $role = Role::query()->where('key', 'institution_admin')->firstOrFail();
        OrganizationMembership::query()->create([
            'user_id' => $admin->id,
            'organization_id' => $org->id,
            'role_id' => $role->id,
            'status' => MembershipStatus::Active,
        ]);

        $category = Category::query()->firstOrFail();
        $barangay = Barangay::query()->firstOrFail();

        $report = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'item_name' => 'DataTable Test Camera',
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'handling_organization_id' => $org->id,
            'custody_organization_id' => $org->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.datatables.reports', [
                'draw' => 3,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => 'DataTable Test Camera'],
            ]))
            ->assertOk()
            ->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsFiltered', 1);

        $firstRow = $response->json('data.0');
        $this->assertStringContainsString('Review', (string) $firstRow[0]);
        $this->assertStringContainsString('DataTable Test Camera', (string) $firstRow[1]);
        $this->assertStringContainsString($report->reference_no, (string) $firstRow[6]);
    }
}
