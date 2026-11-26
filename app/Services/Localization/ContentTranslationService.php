<?php

namespace App\Services\Localization;

use App\Models\ItemReport;
use App\Models\Translation;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Support\Facades\Http;
use Throwable;

class ContentTranslationService
{
    public function __construct(
        public MasterDataCacheService $cache,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->cache->setting('translation.content_enabled', false);
    }

    public function translateReportField(ItemReport $report, string $field, ?string $targetLocale = null): array
    {
        $original = trim((string) $report->getAttribute($field));
        $locale = strtolower(trim($targetLocale ?? app()->getLocale()));
        $supported = array_keys((array) config('localization.supported', ['en' => []]));

        if ($original === '' || ! $this->isEnabled() || ! in_array($locale, $supported, true)) {
            return ['text' => $original, 'translated' => false, 'provider' => null];
        }

        $sourceHash = hash('sha256', $original);

        $cached = Translation::query()
            ->where('translatable_type', ItemReport::class)
            ->where('translatable_id', $report->id)
            ->where('field', $field)
            ->where('locale', $locale)
            ->first();

        if ($cached instanceof Translation && $cached->source_hash === $sourceHash) {
            return [
                'text' => $cached->translated_text,
                'translated' => $cached->translated_text !== $original,
                'provider' => $cached->provider,
            ];
        }

        $url = rtrim((string) config('services.translation.url', ''), '/');
        if ($url === '') {
            return ['text' => $original, 'translated' => false, 'provider' => null];
        }

        $provider = (string) config('services.translation.provider', 'libretranslate');
        $providerTarget = $locale === 'fil' ? 'tl' : $locale;
        $payload = [
            'q' => $original,
            'source' => 'auto',
            'target' => $providerTarget,
            'format' => 'text',
        ];

        $apiKey = (string) config('services.translation.api_key', '');
        if ($apiKey !== '') {
            $payload['api_key'] = $apiKey;
        }

        try {
            $response = Http::timeout((int) config('services.translation.timeout', 5))
                ->acceptJson()
                ->post($url.'/translate', $payload);

            if (! $response->successful()) {
                return ['text' => $original, 'translated' => false, 'provider' => null];
            }

            $translatedText = trim((string) $response->json('translatedText', ''));
            if ($translatedText === '') {
                return ['text' => $original, 'translated' => false, 'provider' => null];
            }

            Translation::query()->updateOrCreate(
                [
                    'translatable_type' => ItemReport::class,
                    'translatable_id' => $report->id,
                    'field' => $field,
                    'locale' => $locale,
                ],
                [
                    'source_hash' => $sourceHash,
                    'translated_text' => $translatedText,
                    'provider' => $provider,
                ]
            );

            return [
                'text' => $translatedText,
                'translated' => $translatedText !== $original,
                'provider' => $provider,
            ];
        } catch (Throwable) {
            return ['text' => $original, 'translated' => false, 'provider' => null];
        }
    }
}
