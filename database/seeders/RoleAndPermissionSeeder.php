<?php

namespace Database\Seeders;

use App\Enums\RoleScope;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['key' => 'public.browse', 'name' => 'Browse Public Listings', 'group' => 'public'],
            ['key' => 'report.create', 'name' => 'Submit Lost or Found Report', 'group' => 'reports'],
            ['key' => 'report.view_own', 'name' => 'View Own Reports', 'group' => 'reports'],
            ['key' => 'report.encode_on_behalf', 'name' => 'Encode Reports on Behalf of Walk-ins', 'group' => 'reports'],
            ['key' => 'report.view_org', 'name' => 'View Organization Reports', 'group' => 'reports'],
            ['key' => 'report.update_org', 'name' => 'Update Pre-Verification Organization Reports', 'group' => 'reports'],
            ['key' => 'report.verify', 'name' => 'Verify and Publish Reports', 'group' => 'reports'],
            ['key' => 'report.reject', 'name' => 'Reject Reports', 'group' => 'reports'],
            ['key' => 'report.photo_exception', 'name' => 'Approve Found Item Photo Exception', 'group' => 'reports'],
            ['key' => 'image.review', 'name' => 'Review Flagged Images', 'group' => 'images'],
            ['key' => 'match.view', 'name' => 'View Item Matches', 'group' => 'matches'],
            ['key' => 'match.review', 'name' => 'Confirm or Dismiss Item Matches', 'group' => 'matches'],
            ['key' => 'claim.create', 'name' => 'Submit Ownership Claim', 'group' => 'claims'],
            ['key' => 'claim.view_own', 'name' => 'View Own Claims', 'group' => 'claims'],
            ['key' => 'claim.view_org', 'name' => 'View Organization Claims', 'group' => 'claims'],
            ['key' => 'claim.evidence_view', 'name' => 'View Private Claim Evidence', 'group' => 'claims'],
            ['key' => 'claim.request_info', 'name' => 'Request Additional Claim Information', 'group' => 'claims'],
            ['key' => 'claim.approve', 'name' => 'Approve Claims', 'group' => 'claims'],
            ['key' => 'claim.reject', 'name' => 'Reject Claims', 'group' => 'claims'],
            ['key' => 'claim.complete', 'name' => 'Complete Claims', 'group' => 'claims'],
            ['key' => 'recovery.record', 'name' => 'Draft Recovery Turnover Record', 'group' => 'recoveries'],
            ['key' => 'recovery.confirm', 'name' => 'Confirm Physical Item Recovery', 'group' => 'recoveries'],
            ['key' => 'custody.manage', 'name' => 'Manage Item Custody Records', 'group' => 'custody'],
            ['key' => 'disposal.view', 'name' => 'View Disposal Monitoring Queue', 'group' => 'disposals'],
            ['key' => 'disposal.propose', 'name' => 'Propose Item Disposal', 'group' => 'disposals'],
            ['key' => 'disposal.approve', 'name' => 'Approve and Record Item Disposal', 'group' => 'disposals'],
            ['key' => 'staff.invite', 'name' => 'Invite Organization Staff', 'group' => 'organizations'],
            ['key' => 'staff.manage', 'name' => 'Manage Organization Staff', 'group' => 'organizations'],
            ['key' => 'organization.manage', 'name' => 'Manage Organizations', 'group' => 'organizations'],
            ['key' => 'masterdata.manage', 'name' => 'Manage Categories and Barangays', 'group' => 'masterdata'],
            ['key' => 'location.manage', 'name' => 'Manage Locations', 'group' => 'masterdata'],
            ['key' => 'settings.manage', 'name' => 'Manage System Settings', 'group' => 'settings'],
            ['key' => 'analytics.view', 'name' => 'View Dashboard and Analytics', 'group' => 'analytics'],
            ['key' => 'export.generate', 'name' => 'Generate CSV and PDF Exports', 'group' => 'analytics'],
            ['key' => 'audit.view', 'name' => 'View Audit Logs', 'group' => 'audit'],
        ];

        $permissionIds = [];

        foreach ($permissions as $data) {
            $permission = Permission::query()->updateOrCreate(
                ['key' => $data['key']],
                ['name' => $data['name'], 'group' => $data['group']]
            );
            $permissionIds[$data['key']] = $permission->id;
        }

        $roles = [
            'platform_super_admin' => [
                'name' => 'Platform Super Administrator',
                'scope' => RoleScope::Platform,
                'description' => 'System-wide administration across all Boracay institutions.',
                'permissions' => array_values(array_diff(array_keys($permissionIds), ['claim.create'])),
            ],
            'institution_admin' => [
                'name' => 'Institution Administrator',
                'scope' => RoleScope::Organization,
                'description' => 'Verifies reports, decides claims, confirms recoveries, and manages institution operations.',
                'permissions' => [
                    'public.browse',
                    'report.create',
                    'report.view_own',
                    'report.encode_on_behalf',
                    'report.view_org',
                    'report.update_org',
                    'report.verify',
                    'report.reject',
                    'report.photo_exception',
                    'image.review',
                    'match.view',
                    'match.review',
                    'claim.view_own',
                    'claim.view_org',
                    'claim.evidence_view',
                    'claim.request_info',
                    'claim.approve',
                    'claim.reject',
                    'claim.complete',
                    'recovery.record',
                    'recovery.confirm',
                    'custody.manage',
                    'disposal.view',
                    'disposal.propose',
                    'disposal.approve',
                    'staff.invite',
                    'staff.manage',
                    'location.manage',
                    'analytics.view',
                    'export.generate',
                    'audit.view',
                ],
            ],
            'institution_staff' => [
                'name' => 'Institution Staff',
                'scope' => RoleScope::Organization,
                'description' => 'Encodes reports, manages custody, and assists with institutional workflows.',
                'permissions' => [
                    'public.browse',
                    'report.create',
                    'report.view_own',
                    'report.encode_on_behalf',
                    'report.view_org',
                    'report.update_org',
                    'image.review',
                    'match.view',
                    'claim.view_own',
                    'claim.view_org',
                    'claim.evidence_view',
                    'claim.request_info',
                    'recovery.record',
                    'custody.manage',
                    'disposal.view',
                    'disposal.propose',
                    'analytics.view',
                ],
            ],
            'public_user' => [
                'name' => 'Public User',
                'scope' => RoleScope::Public,
                'description' => 'Authenticated resident, tourist, finder, or item owner.',
                'permissions' => [
                    'public.browse',
                    'report.create',
                    'report.view_own',
                    'claim.create',
                    'claim.view_own',
                ],
            ],
        ];

        foreach ($roles as $key => $config) {
            $role = Role::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $config['name'],
                    'scope' => $config['scope'],
                    'description' => $config['description'],
                ]
            );

            $role->permissions()->sync(
                array_map(fn (string $permKey): int => $permissionIds[$permKey], $config['permissions'])
            );
        }
    }
}
