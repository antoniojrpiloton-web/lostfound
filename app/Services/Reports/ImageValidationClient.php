<?php

namespace App\Services\Reports;

use App\Enums\ImageValidationStatus;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImageValidationClient
{
    public function __construct(public MasterDataCacheService $masterData) {}

    public function validate(UploadedFile $file, ?string $categorySlug = null): array
    {
        if (! config('services.image_validator.enabled', false)) {
            return [
                'status' => ImageValidationStatus::Review,
                'messages' => [],
                'result' => [
                    'verdict' => 'review',
                    'reason' => 'Automated image validator is disabled; queued for manual review.',
                ],
            ];
        }

        $url = rtrim((string) config('services.image_validator.url'), '/').'/v1/validate';
        $token = (string) config('services.image_validator.token');
        $timeout = (int) config('services.image_validator.timeout', 10);

        $payload = [
            'category_key' => $categorySlug ?? '',
            'blur_reject_threshold' => (string) $this->masterData->setting('image_validation.blur_reject_threshold', 80),
            'blur_review_threshold' => (string) $this->masterData->setting('image_validation.blur_review_threshold', 120),
            'dark_mean_threshold' => (string) $this->masterData->setting('image_validation.dark_mean_threshold', 45),
            'bright_mean_threshold' => (string) $this->masterData->setting('image_validation.bright_mean_threshold', 220),
        ];

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['X-Service-Token' => $token])
                ->attach('image', (string) $file->get(), $file->getClientOriginalName() ?: 'upload.jpg')
                ->post($url, $payload);

            if (! $response->successful()) {
                return $this->handleServiceUnavailable('Image validator returned HTTP '.$response->status());
            }

            $body = $response->json() ?? [];
            $verdict = (string) ($body['verdict'] ?? 'review');
            $messages = is_array($body['messages'] ?? null) ? array_values($body['messages']) : [];

            $status = match ($verdict) {
                'accepted' => ImageValidationStatus::Passed,
                'rejected' => ImageValidationStatus::Rejected,
                default => ImageValidationStatus::Review,
            };

            return [
                'status' => $status,
                'messages' => $messages,
                'result' => $body,
            ];
        } catch (Throwable $exception) {
            Log::warning('Image validation service unavailable.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->handleServiceUnavailable($exception->getMessage());
        }
    }

    protected function handleServiceUnavailable(string $detail): array
    {
        $failMode = (string) $this->masterData->setting('image_validation.fail_mode', 'review');

        if ($failMode === 'reject') {
            return [
                'status' => ImageValidationStatus::Rejected,
                'messages' => [__('The image could not be processed. Please try again later.')],
                'result' => [
                    'verdict' => 'rejected',
                    'service_error' => $detail,
                ],
            ];
        }

        return [
            'status' => ImageValidationStatus::Review,
            'messages' => [],
            'result' => [
                'verdict' => 'review',
                'service_error' => $detail,
            ],
        ];
    }
}
