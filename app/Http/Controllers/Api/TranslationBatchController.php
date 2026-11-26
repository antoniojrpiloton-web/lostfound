<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Localization\ContentTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslationBatchController extends Controller
{
    public function __construct(
        public ContentTranslationService $contentTranslation,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $supported = array_keys((array) config('localization.supported', ['en' => []]));

        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'in:'.implode(',', $supported)],
            'texts' => ['required', 'array', 'min:1', 'max:80'],
            'texts.*' => ['required', 'string', 'max:1000'],
        ]);

        $locale = strtolower(trim((string) ($validated['locale'] ?? app()->getLocale())));
        $translations = $this->contentTranslation->translateBatch($validated['texts'], $locale);

        return response()->json([
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }
}
