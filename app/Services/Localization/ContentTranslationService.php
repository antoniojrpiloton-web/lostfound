<?php

namespace App\Services\Localization;

use App\Models\ItemReport;
use App\Models\Translation;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Support\Facades\Http;
use Throwable;

class ContentTranslationService
{
    /** @var array<string, array{text: string, translated: bool, provider: ?string}> */
    protected array $runtimeCache = [];

    public function __construct(
        public MasterDataCacheService $cache,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->cache->setting('translation.content_enabled', false);
    }

    /**
     * @return array{text: string, translated: bool, provider: ?string}
     */
    public function translateReportField(ItemReport $report, string $field, ?string $targetLocale = null, bool $cacheOnly = false): array
    {
        $original = trim((string) $report->getAttribute($field));

        return $this->translateText($original, $targetLocale, ItemReport::class, (int) $report->id, $field, $cacheOnly);
    }

    public function translateString(?string $text, ?string $targetLocale = null, bool $cacheOnly = false): string
    {
        $original = trim((string) $text);
        if ($original === '') {
            return '';
        }

        return $this->translateText($original, $targetLocale, 'text', 0, null, $cacheOnly)['text'];
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<string, string>
     */
    public function translateBatch(array $texts, ?string $targetLocale = null): array
    {
        $locale = strtolower(trim($targetLocale ?? app()->getLocale()));
        $supported = array_keys((array) config('localization.supported', ['en' => []]));

        $unique = [];
        foreach ($texts as $raw) {
            $trimmed = trim((string) $raw);
            if ($trimmed !== '') {
                $unique[$trimmed] = $trimmed;
            }
        }

        if ($unique === [] || ! $this->isEnabled() || ! in_array($locale, $supported, true) || $locale === 'en') {
            return $unique;
        }

        $results = [];
        $pendingByHash = [];

        foreach ($unique as $original) {
            $jsonTranslated = __($original, [], $locale);
            if (is_string($jsonTranslated) && $jsonTranslated !== '' && $jsonTranslated !== $original) {
                $results[$original] = $jsonTranslated;

                continue;
            }

            $hash = hash('sha256', $original);
            $field = substr($hash, 0, 32);
            $runtimeKey = 'text:0:'.$field.':'.$locale.':'.$hash;

            if (isset($this->runtimeCache[$runtimeKey])) {
                $results[$original] = $this->runtimeCache[$runtimeKey]['text'];

                continue;
            }

            $pendingByHash[$field] = ['original' => $original, 'hash' => $hash, 'runtimeKey' => $runtimeKey];
        }

        if ($pendingByHash !== []) {
            $cachedRows = Translation::query()
                ->where('translatable_type', 'text')
                ->where('translatable_id', 0)
                ->where('locale', $locale)
                ->whereIn('field', array_keys($pendingByHash))
                ->get()
                ->keyBy('field');

            foreach ($pendingByHash as $field => $meta) {
                $cached = $cachedRows->get($field);
                if ($cached instanceof Translation && $cached->source_hash === $meta['hash']) {
                    $results[$meta['original']] = $cached->translated_text;
                    $this->runtimeCache[$meta['runtimeKey']] = [
                        'text' => $cached->translated_text,
                        'translated' => $cached->translated_text !== $meta['original'],
                        'provider' => $cached->provider,
                    ];
                    unset($pendingByHash[$field]);
                }
            }
        }

        if ($pendingByHash !== []) {
            $provider = strtolower(trim((string) config('services.translation.provider', 'google_free')));
            $chunks = array_chunk($pendingByHash, 18, true);

            foreach ($chunks as $chunk) {
                $originals = array_values(array_map(fn (array $m): string => $m['original'], $chunk));
                $translatedList = $provider === 'google_free'
                    ? $this->callGoogleFreeBatch($originals, $locale)
                    : null;

                $idx = 0;
                foreach ($chunk as $field => $meta) {
                    $translated = is_array($translatedList) && isset($translatedList[$idx]) && trim($translatedList[$idx]) !== ''
                        ? trim($translatedList[$idx])
                        : null;

                    if ($translated === null) {
                        $translated = $provider === 'libretranslate'
                            ? $this->callLibreTranslate($meta['original'], $locale)
                            : $this->callGoogleFree($meta['original'], $locale);
                    }

                    $finalText = ($translated !== null && $translated !== '') ? $translated : $meta['original'];
                    $results[$meta['original']] = $finalText;

                    if ($translated !== null && $translated !== '') {
                        Translation::query()->updateOrCreate(
                            [
                                'translatable_type' => 'text',
                                'translatable_id' => 0,
                                'field' => $field,
                                'locale' => $locale,
                            ],
                            [
                                'source_hash' => $meta['hash'],
                                'translated_text' => $finalText,
                                'provider' => $provider,
                            ]
                        );
                    }

                    $this->runtimeCache[$meta['runtimeKey']] = [
                        'text' => $finalText,
                        'translated' => $finalText !== $meta['original'],
                        'provider' => $translated !== null ? $provider : null,
                    ];
                    $idx++;
                }
            }
        }

        return $results;
    }

    /**
     * @return array{text: string, translated: bool, provider: ?string}
     */
    public function translateText(
        string $original,
        ?string $targetLocale = null,
        string $translatableType = 'text',
        int $translatableId = 0,
        ?string $field = null,
        bool $cacheOnly = false,
    ): array {
        $original = trim($original);
        $locale = strtolower(trim($targetLocale ?? app()->getLocale()));
        $supported = array_keys((array) config('localization.supported', ['en' => []]));

        if ($original === '' || ! $this->isEnabled() || ! in_array($locale, $supported, true)) {
            return ['text' => $original, 'translated' => false, 'provider' => null];
        }

        if ($locale === 'en' && preg_match('/^[\x20-\x7E\r\n\t]+$/', $original) === 1) {
            return ['text' => $original, 'translated' => false, 'provider' => null];
        }

        $sourceHash = hash('sha256', $original);
        $resolvedField = $field ?? substr($sourceHash, 0, 32);
        $runtimeKey = $translatableType.':'.$translatableId.':'.$resolvedField.':'.$locale.':'.$sourceHash;

        if (isset($this->runtimeCache[$runtimeKey])) {
            return $this->runtimeCache[$runtimeKey];
        }

        $cached = Translation::query()
            ->where('translatable_type', $translatableType)
            ->where('translatable_id', $translatableId)
            ->where('field', $resolvedField)
            ->where('locale', $locale)
            ->first();

        if (! $cached instanceof Translation && $translatableType !== 'text') {
            $cached = Translation::query()
                ->where('translatable_type', 'text')
                ->where('translatable_id', 0)
                ->where('field', substr($sourceHash, 0, 32))
                ->where('locale', $locale)
                ->first();
        }

        if ($cached instanceof Translation && $cached->source_hash === $sourceHash) {
            return $this->runtimeCache[$runtimeKey] = [
                'text' => $cached->translated_text,
                'translated' => $cached->translated_text !== $original,
                'provider' => $cached->provider,
            ];
        }

        if ($cacheOnly) {
            return $this->runtimeCache[$runtimeKey] = ['text' => $original, 'translated' => false, 'provider' => null];
        }

        $provider = strtolower(trim((string) config('services.translation.provider', 'google_free')));
        $translatedText = $provider === 'libretranslate'
            ? $this->callLibreTranslate($original, $locale)
            : $this->callGoogleFree($original, $locale);

        if ($translatedText === null || $translatedText === '') {
            return $this->runtimeCache[$runtimeKey] = ['text' => $original, 'translated' => false, 'provider' => null];
        }

        Translation::query()->updateOrCreate(
            [
                'translatable_type' => $translatableType,
                'translatable_id' => $translatableId,
                'field' => $resolvedField,
                'locale' => $locale,
            ],
            [
                'source_hash' => $sourceHash,
                'translated_text' => $translatedText,
                'provider' => $provider,
            ]
        );

        return $this->runtimeCache[$runtimeKey] = [
            'text' => $translatedText,
            'translated' => $translatedText !== $original,
            'provider' => $provider,
        ];
    }

    protected function callLibreTranslate(string $original, string $locale): ?string
    {
        $url = rtrim((string) config('services.translation.url', ''), '/');
        if ($url === '') {
            return null;
        }

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
                return null;
            }

            $translatedText = trim((string) $response->json('translatedText', ''));

            return $translatedText !== '' ? $translatedText : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function callGoogleFree(string $original, string $locale): ?string
    {
        $target = match ($locale) {
            'fil' => 'tl',
            'zh' => 'zh-CN',
            default => $locale,
        };

        $timeout = (int) config('services.translation.timeout', 5);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'gtx',
                    'sl' => 'auto',
                    'tl' => $target,
                    'dt' => 't',
                    'q' => $original,
                ]);

            if ($response->successful()) {
                $segments = $response->json('0');
                if (is_array($segments)) {
                    $combined = '';
                    foreach ($segments as $segment) {
                        if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
                            $combined .= $segment[0];
                        }
                    }

                    $combined = trim($combined);
                    if ($combined !== '') {
                        return $combined;
                    }
                }
            }
        } catch (Throwable) {
        }

        try {
            $fallback = Http::timeout($timeout)
                ->acceptJson()
                ->get('https://api.mymemory.translated.net/get', [
                    'q' => $original,
                    'langpair' => 'en|'.$target,
                ]);

            if ($fallback->successful()) {
                $translatedText = trim((string) $fallback->json('responseData.translatedText', ''));

                return $translatedText !== '' ? $translatedText : null;
            }
        } catch (Throwable) {
        }

        return null;
    }

    /**
     * @param  array<int, string>  $originals
     * @return array<int, string>|null
     */
    protected function callGoogleFreeBatch(array $originals, string $locale): ?array
    {
        if (count($originals) === 1) {
            $single = $this->callGoogleFree($originals[0], $locale);

            return $single !== null ? [$single] : null;
        }

        $separator = "\n###BLFSEP###\n";
        $joined = implode($separator, $originals);
        $translatedJoined = $this->callGoogleFree($joined, $locale);

        if ($translatedJoined === null || $translatedJoined === '') {
            return null;
        }

        $parts = preg_split('/\s*###\s*BLFSEP\s*###\s*/iu', $translatedJoined);
        if (! is_array($parts) || count($parts) !== count($originals)) {
            return null;
        }

        return array_map(static fn (string $part): string => trim($part), $parts);
    }
}
