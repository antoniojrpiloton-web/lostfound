<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ItemStatus;
use App\Enums\MatchStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemMatch;
use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndRecoveryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_end_to_end_lost_to_match_to_claim_to_turnover_pdf_to_analytics_pdf_flow(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');

        $tourist = User::factory()->create([
            'name' => 'Maria Clara Tourist',
            'email' => 'maria.tourist@example.com',
        ]);

        $admin = User::factory()->create([
            'name' => 'Officer Juan Balabag',
            'email' => 'officer.balabag@example.com',
        ]);

        $balabag = Barangay::query()->where('name', 'Balabag')->firstOrFail();
        $category = Category::query()->where('slug', 'mobile-phones')->firstOrFail();
        $location = Location::query()->where('barangay_id', $balabag->id)->first();
        $occurredDate = now()->toDateString();

        $lostResponse = $this->actingAs($tourist)->post(route('reports.lost.store'), [
            'category_id' => $category->id,
            'barangay_id' => $balabag->id,
            'location_id' => $location?->id,
            'item_name' => 'Blue Titanium iPhone 15 Pro',
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'color' => 'Blue Titanium',
            'description' => 'Lost near Station 2 beachfront with clear MagSafe case and coral strap.',
            'distinguishing_features' => 'Small scratch on bottom-left corner, Boracay sticker inside case.',
            'occurred_date' => $occurredDate,
            'contact_phone' => '09171234567',
            'images' => [UploadedFile::fake()->image('lost-iphone.jpg', 800, 600)],
        ]);

        $lostReport = ItemReport::query()->where('type', ReportType::Lost)->latest('id')->firstOrFail();
        $lostResponse->assertRedirect(route('reports.show_own', $lostReport));
        $this->assertSame(VerificationStatus::Pending, $lostReport->verification_status);

        $organization = Organization::query()->findOrFail($lostReport->handling_organization_id);
        $adminRole = Role::query()->where('key', 'institution_admin')->firstOrFail();

        OrganizationMembership::query()->create([
            'user_id' => $admin->id,
            'organization_id' => $organization->id,
            'role_id' => $adminRole->id,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.verify', $lostReport), ['remarks' => 'Verified tourist lost report.'])
            ->assertRedirect(route('admin.reports.show', $lostReport));

        $lostReport->refresh();
        $this->assertSame(VerificationStatus::Verified, $lostReport->verification_status);
        $this->assertSame(ItemStatus::Open, $lostReport->item_status);

        $foundResponse = $this->actingAs($admin)->post(route('admin.found.store'), [
            'category_id' => $category->id,
            'barangay_id' => $balabag->id,
            'location_id' => $location?->id,
            'item_name' => 'Blue Titanium iPhone 15 Pro',
            'brand' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'color' => 'Blue Titanium',
            'description' => 'Found near Station 2 beachfront with clear MagSafe case and coral strap.',
            'distinguishing_features' => 'Boracay sticker visible inside clear case.',
            'occurred_date' => $occurredDate,
            'finder_name' => 'Station 2 Lifeguard',
            'finder_contact' => '09181112222',
            'photos' => [UploadedFile::fake()->image('found-iphone.jpg', 800, 600)],
        ]);

        $foundReport = ItemReport::query()->where('type', ReportType::Found)->latest('id')->firstOrFail();
        $foundResponse->assertRedirect();

        if ($foundReport->verification_status === VerificationStatus::Pending) {
            $this->actingAs($admin)
                ->patch(route('admin.reports.verify', $foundReport), ['remarks' => 'Verified desk found item.'])
                ->assertRedirect(route('admin.reports.show', $foundReport));
            $foundReport->refresh();
        }

        $this->assertSame(VerificationStatus::Verified, $foundReport->verification_status);
        $this->assertSame(ItemStatus::InCustody, $foundReport->item_status);

        $match = ItemMatch::query()
            ->where('lost_report_id', $lostReport->id)
            ->where('found_report_id', $foundReport->id)
            ->first();
        $this->assertNotNull($match);

        $this->actingAs($admin)
            ->patch(route('admin.matches.decide', $match), ['status' => 'possible'])
            ->assertRedirect();

        $match->refresh();
        $this->assertSame(MatchStatus::Possible, $match->status);

        $claimResponse = $this->actingAs($tourist)->post(route('claims.store', $foundReport->reference_no), [
            'ownership_description' => 'This is my Blue Titanium iPhone 15 Pro lost at Station 2 with a clear MagSafe case.',
            'distinguishing_details' => 'Has a Boracay sticker inside the case and small bottom-left scratch.',
            'lost_report_id' => $lostReport->id,
            'evidence' => [UploadedFile::fake()->image('receipt-box.jpg', 800, 600)],
            'evidence_description' => 'Purchase box & Apple receipt photo',
        ]);

        $claim = Claim::query()->where('item_report_id', $foundReport->id)->firstOrFail();
        $claimResponse->assertRedirect(route('claims.show_own', $claim));
        $this->assertSame(ClaimStatus::Submitted, $claim->status);

        $this->actingAs($admin)
            ->patch(route('admin.claims.approve', $claim), ['decision_remarks' => 'IMEI and proof of purchase verified.'])
            ->assertRedirect(route('admin.claims.show', $claim));

        $claim->refresh();
        $this->assertSame(ClaimStatus::Approved, $claim->status);

        $this->actingAs($admin)
            ->post(route('admin.claims.recovery.store', $claim), [
                'received_by_name' => 'Maria Clara Tourist',
                'id_type' => 'Philippine Passport',
                'id_last4' => '7890',
                'verification_outcome' => 'verified',
                'remarks' => 'Claimant unlocked phone on-site and signed receipt.',
                'confirm_now' => 1,
            ])
            ->assertRedirect(route('admin.claims.show', $claim));

        $recovery = ItemRecovery::query()->where('claim_id', $claim->id)->firstOrFail();
        $this->assertNotNull($recovery->confirmed_by);

        $foundReport->refresh();
        $this->assertSame(ItemStatus::Returned, $foundReport->item_status);

        $receiptPdf = $this->actingAs($admin)->get(route('admin.recoveries.receipt_pdf', $recovery));
        $receiptPdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $receiptPdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', (string) $receiptPdf->getContent());

        $this->actingAs($admin)->get(route('admin.analytics.index'))->assertOk();

        $analyticsCsv = $this->actingAs($admin)->get(route('admin.analytics.export.csv'));
        $analyticsCsv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $analyticsCsv->headers->get('content-type'));

        $analyticsPdf = $this->actingAs($admin)->get(route('admin.analytics.export.pdf'));
        $analyticsPdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $analyticsPdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', (string) $analyticsPdf->getContent());
    }
}
