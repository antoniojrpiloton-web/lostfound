<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemReport;
use App\Models\SystemSetting;
use App\Models\Translation;
use App\Models\User;
use App\Services\Auth\GoogleAuthService;
use App\Services\Localization\ContentTranslationService;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_language_persists_in_session_and_user_profile_and_translates_ui(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->get(route('home', ['lang' => 'ko']))
            ->assertOk()
            ->assertSee('분실물 신고')
            ->assertSee('습득물 신고');

        $this->assertSame('ko', $user->fresh()->locale);
        $this->assertSame('ko', session('locale'));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('분실물 신고');

        $this->actingAs($user)
            ->get(route('home', ['lang' => 'fil']))
            ->assertOk()
            ->assertSee('Mag-ulat ng Nawala');

        $this->actingAs($user)
            ->get(route('home', ['lang' => 'zh']))
            ->assertOk()
            ->assertSee('挂失物品');

        $this->actingAs($user)
            ->get(route('home', ['lang' => 'ja']))
            ->assertOk()
            ->assertSee('遺失物を届出');

        $this->actingAs($user)
            ->get(route('home', ['lang' => 'es']))
            ->assertOk()
            ->assertSee('Reportar perdido');
    }

    public function test_foreign_tourist_browser_accept_language_is_auto_detected(): void
    {
        $this->withHeaders(['Accept-Language' => 'ko-KR,ko;q=0.9,en;q=0.8'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('보라카이 분실물 센터');

        $this->withHeaders(['Accept-Language' => 'ja-JP,ja;q=0.9'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('ボラカイ遺失物管理システム');

        $this->withHeaders(['Accept-Language' => 'tl-PH,tl;q=0.9'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Sistema ng Nawala at Nahanap sa Boracay');
    }

    public function test_unsupported_language_code_falls_back_to_english(): void
    {
        $this->get(route('home', ['lang' => 'xx']))
            ->assertOk()
            ->assertSee('Boracay Lost and Found System');
    }

    public function test_google_first_registration_syncs_supported_google_locale(): void
    {
        $service = app(GoogleAuthService::class);
        $request = Request::create('/auth/google/callback', 'GET');

        $koreanGoogleUser = (new SocialiteUser)->map([
            'id' => 'google-ko-1001',
            'name' => 'Minjun Kim',
            'email' => 'minjun@example.com',
            'avatar' => null,
        ])->setRaw(['email_verified' => true, 'locale' => 'ko-KR']);

        $createdKo = $service->resolveUser($koreanGoogleUser, $request);
        $this->assertSame('ko', $createdKo->locale);

        $filipinoGoogleUser = (new SocialiteUser)->map([
            'id' => 'google-tl-1002',
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'avatar' => null,
        ])->setRaw(['email_verified' => true, 'locale' => 'tl-PH']);

        $createdFil = $service->resolveUser($filipinoGoogleUser, $request);
        $this->assertSame('fil', $createdFil->locale);
    }

    public function test_content_translation_caches_result_preserves_original_and_falls_back_on_failure(): void
    {
        $category = Category::factory()->create();
        $barangay = Barangay::factory()->create();
        $report = ItemReport::factory()->create([
            'type' => ReportType::Found,
            'category_id' => $category->id,
            'barangay_id' => $barangay->id,
            'verification_status' => VerificationStatus::Verified,
            'item_status' => ItemStatus::InCustody,
            'is_public' => true,
            'description' => 'Black leather wallet found near Station 2.',
        ]);

        config()->set('services.translation.url', 'http://translator.local');
        config()->set('services.translation.provider', 'libretranslate');

        Http::fake([
            'http://translator.local/translate' => Http::sequence()
                ->push(['translatedText' => '스테이션 2 근처에서 발견된 검은색 가죽 지갑입니다.'], 200)
                ->push([], 500),
        ]);

        $translationService = app(ContentTranslationService::class);

        $disabledResult = $translationService->translateReportField($report, 'description', 'ko');
        $this->assertFalse($disabledResult['translated']);
        $this->assertSame('Black leather wallet found near Station 2.', $disabledResult['text']);
        Http::assertNothingSent();

        SystemSetting::query()->updateOrCreate(
            ['key' => 'translation.content_enabled'],
            ['group' => 'localization', 'value' => ['value' => true]]
        );
        app(MasterDataCacheService::class)->forgetSettings();

        $firstCall = $translationService->translateReportField($report, 'description', 'ko');
        $this->assertTrue($firstCall['translated']);
        $this->assertSame('스테이션 2 근처에서 발견된 검은색 가죽 지갑입니다.', $firstCall['text']);
        $this->assertSame('Black leather wallet found near Station 2.', $report->fresh()->description);
        $this->assertSame(1, Translation::query()->count());

        $secondCall = $translationService->translateReportField($report, 'description', 'ko');
        $this->assertTrue($secondCall['translated']);
        $this->assertSame('스테이션 2 근처에서 발견된 검은색 가죽 지갑입니다.', $secondCall['text']);
        Http::assertSentCount(1);

        $this->get(route('public.items.show', ['referenceNo' => $report->reference_no, 'lang' => 'ko']))
            ->assertOk()
            ->assertSee('스테이션 2 근처에서 발견된 검은색 가죽 지갑입니다.')
            ->assertSee('Black leather wallet found near Station 2.');

        $failedCall = $translationService->translateReportField($report, 'description', 'ja');
        $this->assertFalse($failedCall['translated']);
        $this->assertSame('Black leather wallet found near Station 2.', $failedCall['text']);
    }
}
