<?php

namespace App\Services\Matching;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\ImageEmbedding;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\User;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VisualSearchService
{
    public function __construct(public MasterDataCacheService $masterData) {}

    public function isVisualSearchEnabled(): bool
    {
        return (bool) $this->masterData->setting('matching.visual_search_enabled', false);
    }

    public function isImageMatchingEnabled(): bool
    {
        return (bool) $this->masterData->setting('matching.image_enabled', false);
    }

    public function embedRawBytes(string $bytes, string $filename = 'image.jpg'): array
    {
        $url = rtrim((string) config('services.image_validator.url'), '/').'/v1/embed';
        $token = (string) config('services.image_validator.token');
        $timeout = (int) config('services.image_validator.timeout', 10);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['X-Service-Token' => $token])
                ->attach('image', $bytes, $filename)
                ->post($url);

            if ($response->successful()) {

                $body = $response->json() ?? [];
                $vector = is_array($body['vector'] ?? null)
                    ? array_values(array_map('floatval', $body['vector']))
                    : [];

                if ((bool) ($body['supported'] ?? false) && $vector !== []) {
                    return [
                        'supported' => true,
                        'model_version' => (string) ($body['model_version'] ?? 'clip-v1'),
                        'vector' => $vector,
                    ];
                }
            }
        } catch (Throwable $exception) {
            Log::info('External visual embedding service unavailable; falling back to local PHP GD fingerprint.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->embedWithPhpGd($bytes);
    }

    protected function embedWithPhpGd(string $bytes): array
    {
        if (! function_exists('imagecreatefromstring') || $bytes === '') {
            return [
                'supported' => false,
                'model_version' => 'none',
                'vector' => [],
                'error' => __('Visual search is unavailable on this server.'),
            ];
        }

        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return [
                'supported' => false,
                'model_version' => 'none',
                'vector' => [],
                'error' => __('Unable to decode uploaded image for visual search.'),
            ];
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $vector = [];

        $fgX = (int) round($srcW * 0.18);
        $fgY = (int) round($srcH * 0.18);
        $fgW = max(1, (int) round($srcW * 0.64));
        $fgH = max(1, (int) round($srcH * 0.64));

        $grid32 = imagecreatetruecolor(32, 32);
        imagecopyresampled($grid32, $source, 0, 0, $fgX, $fgY, 32, 32, $fgW, $fgH);

        $colorBins = array_fill(0, 48, 0.0);
        $luma32 = [];
        $weightSum = 0.0;

        for ($y = 0; $y < 32; $y++) {
            $row = [];
            $dy = ($y - 15.5) / 16.0;
            for ($x = 0; $x < 32; $x++) {
                $dx = ($x - 15.5) / 16.0;
                $centerWeight = 1.0 + (0.6 * max(0.0, 1.0 - (($dx * $dx + $dy * $dy) * 0.65)));

                $rgb = imagecolorat($grid32, $x, $y);
                $r = (($rgb >> 16) & 0xFF) / 255.0;
                $g = (($rgb >> 8) & 0xFF) / 255.0;
                $b = ($rgb & 0xFF) / 255.0;

                $luma = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $row[] = $luma;

                $max = max($r, $g, $b);
                $min = min($r, $g, $b);
                $delta = $max - $min;
                $s = $max > 0.0001 ? ($delta / $max) : 0.0;
                $v = $max;

                if ($s < 0.22 || $v < 0.18) {
                    $neutralBin = min(11, (int) floor($luma * 12));
                    $colorBins[36 + $neutralBin] += $centerWeight;
                } else {
                    if ($max === $r) {
                        $h = fmod((($g - $b) / $delta) + 6.0, 6.0) / 6.0;
                    } elseif ($max === $g) {
                        $h = ((($b - $r) / $delta) + 2.0) / 6.0;
                    } else {
                        $h = ((($r - $g) / $delta) + 4.0) / 6.0;
                    }
                    $hBin = min(11, (int) floor($h * 12));
                    $svBin = $v < 0.45 ? 0 : ($s < 0.52 ? 1 : 2);
                    $colorBins[($hBin * 3) + $svBin] += $centerWeight;
                }

                $weightSum += $centerWeight;
            }
            $luma32[] = $row;
        }
        imagedestroy($grid32);

        $totalWeight = max(1.0, $weightSum);
        foreach ($colorBins as $count) {
            $vector[] = sqrt($count / $totalWeight) * 1.45;
        }

        $edgeBins = array_fill(0, 8, 0.0);
        $quadEdges = array_fill(0, 8, 0.0);
        for ($y = 1; $y < 31; $y++) {
            for ($x = 1; $x < 31; $x++) {
                $gx = $luma32[$y][$x + 1] - $luma32[$y][$x - 1];
                $gy = $luma32[$y + 1][$x] - $luma32[$y - 1][$x];
                $mag = sqrt($gx * $gx + $gy * $gy);
                if ($mag > 0.025) {
                    $angle = atan2($gy, $gx);
                    $bin = min(7, (int) floor((($angle + M_PI) / (2 * M_PI)) * 8));
                    $edgeBins[$bin] += $mag;
                    $qIdx = ($y >= 16 ? 4 : 0) + (int) floor($x / 8);
                    $quadEdges[$qIdx] += $mag;
                }
            }
        }
        $edgeSum = max(0.0001, array_sum($edgeBins));
        $edgeMean = 1.0 / 8.0;
        foreach ($edgeBins as $e) {
            $vector[] = (($e / $edgeSum) - $edgeMean) * 2.2;
        }
        $quadSum = max(0.0001, array_sum($quadEdges));
        $quadMean = 1.0 / 8.0;
        foreach ($quadEdges as $qe) {
            $vector[] = (($qe / $quadSum) - $quadMean) * 1.8;
        }

        $grid8 = imagecreatetruecolor(8, 8);
        imagecopyresampled($grid8, $source, 0, 0, $fgX, $fgY, 8, 8, $fgW, $fgH);
        $lumaValues = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($grid8, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lumaValues[] = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255.0;
            }
        }
        imagedestroy($grid8);
        imagedestroy($source);

        $meanLuma = array_sum($lumaValues) / max(1, count($lumaValues));
        $variance = 0.0;
        foreach ($lumaValues as $val) {
            $diff = $val - $meanLuma;
            $variance += $diff * $diff;
        }
        $stdLuma = max(0.05, sqrt($variance / max(1, count($lumaValues))));
        foreach ($lumaValues as $val) {
            $vector[] = (($val - $meanLuma) / $stdLuma) * 0.085;
        }

        $norm = sqrt(array_sum(array_map(fn (float $v): float => $v * $v, $vector)));
        if ($norm <= 0.0) {
            $vector = array_fill(0, count($vector), 1.0 / sqrt(max(1, count($vector))));
        } else {
            $vector = array_map(fn (float $v): float => round($v / $norm, 6), $vector);
        }

        return [
            'supported' => true,
            'model_version' => 'php-gd-v3',
            'vector' => $vector,
        ];
    }

    public function indexItemImage(ItemImage $image): ?ImageEmbedding
    {
        if (! Storage::disk('local')->exists($image->private_path)) {
            return null;
        }

        $bytes = Storage::disk('local')->get($image->private_path);
        $embedded = $this->embedRawBytes($bytes, basename($image->private_path));

        if (! $embedded['supported'] || $embedded['vector'] === []) {
            return null;
        }

        return ImageEmbedding::query()->updateOrCreate(
            ['item_image_id' => $image->id],
            [
                'item_report_id' => $image->item_report_id,
                'model_version' => $embedded['model_version'],
                'dimensions' => count($embedded['vector']),
                'vector' => $embedded['vector'],
            ]
        );
    }

    public function searchPublicItemsByPhotos(array $photos, ?string $type = null, int $limit = 12, ?User $orgScopeUser = null): array
    {
        $bestPerReport = [];
        $lastError = null;
        $anySucceeded = false;

        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }

            $outcome = $this->searchPublicItemsByPhoto($photo, $type, $limit, $orgScopeUser);
            if ($outcome['error'] !== null) {
                $lastError = $outcome['error'];

                continue;
            }

            $anySucceeded = true;
            foreach ($outcome['results'] as $entry) {

                $report = $entry['report'];
                $sim = (float) $entry['similarity'];
                if (! isset($bestPerReport[$report->id]) || $sim > $bestPerReport[$report->id]['similarity']) {
                    $bestPerReport[$report->id] = [
                        'report' => $report,
                        'similarity' => $sim,
                        'breakdown' => $entry['breakdown'] ?? null,
                    ];
                }
            }
        }

        return [
            'results' => collect($bestPerReport)->sortByDesc('similarity')->take($limit)->values(),
            'error' => $anySucceeded ? null : $lastError,
        ];
    }

    public function searchLostItemsByPhotos(array $photos, int $limit = 6): Collection
    {
        $bestPerReport = [];

        foreach ($photos as $photo) {
            if (! $photo instanceof UploadedFile) {
                continue;
            }

            foreach ($this->searchLostItemsByPhoto($photo, $limit) as $entry) {

                $report = $entry['report'];
                $sim = (float) $entry['similarity'];
                if (! isset($bestPerReport[$report->id]) || $sim > $bestPerReport[$report->id]['similarity']) {
                    $bestPerReport[$report->id] = [
                        'report' => $report,
                        'similarity' => $sim,
                    ];
                }
            }
        }

        return collect($bestPerReport)->sortByDesc('similarity')->take($limit)->values();
    }

    public function searchFoundItemsByPhoto(UploadedFile $photo, int $limit = 12): array
    {
        return $this->searchPublicItemsByPhoto($photo, ReportType::Found->value, $limit);
    }

    public function searchPublicItemsByPhoto(UploadedFile $photo, ?string $type = null, int $limit = 12, ?User $orgScopeUser = null): array
    {
        $rawBytes = (string) $photo->get();
        $sha256 = hash('sha256', $rawBytes);
        $embedded = $this->embedRawBytes($rawBytes, $photo->getClientOriginalName() ?: 'query.jpg');

        if (! $embedded['supported'] || $embedded['vector'] === []) {
            return [
                'results' => collect(),
                'error' => $embedded['error'] ?? __('Visual search model is not enabled on the image service. Please use text and filter search instead.'),
            ];
        }

        $minScore = (float) $this->masterData->setting('matching.visual_search_min_score', 0.64);
        $queryVector = $embedded['vector'];
        $modelVersion = $embedded['model_version'];

        $bestPerReport = [];

        $scopeFilter = function ($query) use ($type, $orgScopeUser): void {
            if ($orgScopeUser instanceof User) {
                $query->visibleTo($orgScopeUser);
            } else {
                $query->publiclyVisible();
            }

            if ($type !== null && in_array($type, ReportType::values(), true)) {
                $query->where('type', $type);
            }
        };

        $exactImages = ItemImage::query()
            ->where('sha256', $sha256)
            ->whereHas('itemReport', $scopeFilter)
            ->with(['itemReport.category.parent', 'itemReport.barangay', 'itemReport.location', 'itemReport.custodyOrganization'])
            ->get();

        foreach ($exactImages as $exactImage) {
            if ($exactImage->itemReport instanceof ItemReport) {
                $bestPerReport[$exactImage->item_report_id] = [
                    'report' => $exactImage->itemReport,
                    'similarity' => 1.0,
                    'breakdown' => [
                        'engine' => 'exact',
                        'label' => 'Exact Photo Match',
                        'color' => 100,
                        'shape' => 100,
                        'layout' => 100,
                    ],
                ];
            }
        }

        $embeddings = ImageEmbedding::query()
            ->where('model_version', $modelVersion)
            ->whereHas('itemReport', $scopeFilter)
            ->with(['itemReport.category.parent', 'itemReport.barangay', 'itemReport.location', 'itemReport.custodyOrganization'])
            ->get();

        $allScored = [];

        foreach ($embeddings as $embedding) {
            $vec = is_array($embedding->vector) ? array_map('floatval', $embedding->vector) : [];
            $sim = $this->cosineSimilarity($queryVector, $vec);

            if (! $embedding->itemReport instanceof ItemReport) {
                continue;
            }

            $reportId = $embedding->item_report_id;
            if (! isset($allScored[$reportId]) || $sim > $allScored[$reportId]['similarity']) {
                $allScored[$reportId] = [
                    'report' => $embedding->itemReport,
                    'similarity' => round($sim, 4),
                    'breakdown' => $this->computeVisualBreakdown($queryVector, $vec, $modelVersion, $sim),
                ];
            }

            if ($sim < $minScore) {
                continue;
            }

            if (! isset($bestPerReport[$reportId]) || $sim > $bestPerReport[$reportId]['similarity']) {
                $bestPerReport[$reportId] = $allScored[$reportId];
            }
        }

        $sorted = collect($bestPerReport)
            ->sortByDesc('similarity')
            ->values();

        if ($sorted->isEmpty() && $allScored !== []) {
            $fallbackFloor = max(0.50, $minScore - 0.14);
            $fallbackSorted = collect($allScored)
                ->filter(fn (array $entry): bool => (float) $entry['similarity'] >= $fallbackFloor)
                ->sortByDesc('similarity')
                ->values();

            if ($fallbackSorted->isNotEmpty()) {
                $fallbackTop = (float) $fallbackSorted->first()['similarity'];
                $sorted = $fallbackSorted
                    ->filter(fn (array $entry): bool => (float) $entry['similarity'] >= ($fallbackTop - 0.06))
                    ->values();
            }
        }

        $topSimilarity = (float) ($sorted->first()['similarity'] ?? 0.0);
        if ($topSimilarity >= 0.88) {
            $adaptiveFloor = max($minScore, $topSimilarity - 0.20);
            $sorted = $sorted->filter(fn (array $entry): bool => (float) $entry['similarity'] >= $adaptiveFloor)->values();
        }

        $ranked = $sorted
            ->take($limit)
            ->values();

        return [
            'results' => $ranked,
            'error' => null,
        ];
    }

    public function searchLostItemsByPhoto(UploadedFile $photo, int $limit = 6): Collection
    {
        if (! $this->isVisualSearchEnabled() && ! $this->isImageMatchingEnabled()) {
            return collect();
        }

        $embedded = $this->embedRawBytes((string) $photo->get(), $photo->getClientOriginalName() ?: 'precheck.jpg');

        if (! $embedded['supported'] || $embedded['vector'] === []) {
            return collect();
        }

        $minScore = (float) $this->masterData->setting('matching.visual_search_min_score', 0.64);
        $queryVector = $embedded['vector'];
        $modelVersion = $embedded['model_version'];

        $embeddings = ImageEmbedding::query()
            ->where('model_version', $modelVersion)
            ->whereHas('itemReport', function ($query): void {
                $query->where('type', ReportType::Lost)
                    ->where('verification_status', VerificationStatus::Verified)
                    ->where('item_status', ItemStatus::Open);
            })
            ->with(['itemReport.category', 'itemReport.barangay'])
            ->get();

        $bestPerReport = [];
        foreach ($embeddings as $embedding) {
            $vec = is_array($embedding->vector) ? array_map('floatval', $embedding->vector) : [];
            $sim = $this->cosineSimilarity($queryVector, $vec);

            if ($sim < $minScore || ! $embedding->itemReport instanceof ItemReport) {
                continue;
            }

            $reportId = $embedding->item_report_id;
            if (! isset($bestPerReport[$reportId]) || $sim > $bestPerReport[$reportId]['similarity']) {
                $bestPerReport[$reportId] = [
                    'report' => $embedding->itemReport,
                    'similarity' => round($sim, 4),
                ];
            }
        }

        return collect($bestPerReport)->sortByDesc('similarity')->take($limit)->values();
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $count = count($a);
        if ($count === 0 || $count !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $dot / (sqrt($normA) * sqrt($normB))));
    }

    public function compareReports(ItemReport $source, ItemReport $target): ?array
    {
        $source->loadMissing('images');
        $target->loadMissing('images');

        if ($source->images->isEmpty() || $target->images->isEmpty()) {
            return null;
        }

        foreach ($source->images as $srcImg) {
            foreach ($target->images as $tgtImg) {
                if ($srcImg->sha256 !== '' && $srcImg->sha256 === $tgtImg->sha256) {
                    return [
                        'overall' => 100,
                        'source_image_id' => $srcImg->id,
                        'target_image_id' => $tgtImg->id,
                        'breakdown' => [
                            'engine' => 'exact',
                            'color' => 100,
                            'shape' => 100,
                            'layout' => 100,
                        ],
                    ];
                }
            }
        }

        $sourceEmbeddings = ImageEmbedding::query()->where('item_report_id', $source->id)->get();
        $targetEmbeddings = ImageEmbedding::query()->where('item_report_id', $target->id)->get();

        $best = null;

        foreach ($sourceEmbeddings as $srcEmb) {
            $srcVec = is_array($srcEmb->vector) ? array_map('floatval', $srcEmb->vector) : [];
            if ($srcVec === []) {
                continue;
            }

            foreach ($targetEmbeddings as $tgtEmb) {
                if ($srcEmb->model_version !== $tgtEmb->model_version) {
                    continue;
                }

                $tgtVec = is_array($tgtEmb->vector) ? array_map('floatval', $tgtEmb->vector) : [];
                if ($tgtVec === []) {
                    continue;
                }

                $sim = $this->cosineSimilarity($srcVec, $tgtVec);
                if ($best === null || $sim > $best['_raw']) {
                    $best = [
                        '_raw' => $sim,
                        'overall' => (int) round($sim * 100),
                        'source_image_id' => $srcEmb->item_image_id,
                        'target_image_id' => $tgtEmb->item_image_id,
                        'breakdown' => $this->computeVisualBreakdown($srcVec, $tgtVec, $srcEmb->model_version, $sim),
                    ];
                }
            }
        }

        if ($best === null) {
            return null;
        }

        unset($best['_raw']);

        return $best;
    }

    public function findVisualCounterparts(ItemReport $report, int $limit = 6): Collection
    {
        $sourceEmbeddings = ImageEmbedding::query()->where('item_report_id', $report->id)->get();
        if ($sourceEmbeddings->isEmpty()) {
            return collect();
        }

        $oppositeType = $report->type === ReportType::Found ? ReportType::Lost : ReportType::Found;
        $minScore = (float) $this->masterData->setting('matching.visual_search_min_score', 0.64);
        $modelVersions = $sourceEmbeddings->pluck('model_version')->unique()->values()->all();

        $candidates = ImageEmbedding::query()
            ->whereIn('model_version', $modelVersions)
            ->where('item_report_id', '!=', $report->id)
            ->whereHas('itemReport', function ($query) use ($oppositeType): void {
                $query->where('type', $oppositeType->value)
                    ->whereNotIn('item_status', [ItemStatus::Closed->value, ItemStatus::Disposed->value]);
            })
            ->with(['itemReport.category', 'itemReport.barangay', 'itemReport.images'])
            ->get();

        $report->loadMissing('category');
        $sourceRootCatId = $report->category?->parent_id ?? $report->category_id;
        $crossCategoryMinScore = max(0.82, $minScore + 0.18);

        $bestPerReport = [];

        foreach ($sourceEmbeddings as $srcEmb) {
            $srcVec = is_array($srcEmb->vector) ? array_map('floatval', $srcEmb->vector) : [];
            if ($srcVec === []) {
                continue;
            }

            foreach ($candidates as $candEmb) {
                if ($candEmb->model_version !== $srcEmb->model_version || ! $candEmb->itemReport instanceof ItemReport) {
                    continue;
                }

                $candVec = is_array($candEmb->vector) ? array_map('floatval', $candEmb->vector) : [];
                $sim = $this->cosineSimilarity($srcVec, $candVec);

                $candRootCatId = $candEmb->itemReport->category?->parent_id ?? $candEmb->itemReport->category_id;
                $sameFamily = $sourceRootCatId === null
                    || $candRootCatId === null
                    || $sourceRootCatId === $candRootCatId
                    || $candEmb->itemReport->category?->slug === 'others'
                    || $report->category?->slug === 'others';

                $requiredScore = $sameFamily ? $minScore : $crossCategoryMinScore;
                if ($sim < $requiredScore) {
                    continue;
                }

                $rid = $candEmb->item_report_id;
                if (! isset($bestPerReport[$rid]) || $sim > $bestPerReport[$rid]['similarity']) {
                    $bestPerReport[$rid] = [
                        'report' => $candEmb->itemReport,
                        'similarity' => round($sim, 4),
                        'visual' => [
                            'overall' => (int) round($sim * 100),
                            'source_image_id' => $srcEmb->item_image_id,
                            'target_image_id' => $candEmb->item_image_id,
                            'breakdown' => $this->computeVisualBreakdown($srcVec, $candVec, $srcEmb->model_version, $sim),
                        ],
                    ];
                }
            }
        }

        return collect($bestPerReport)->sortByDesc('similarity')->take($limit)->values();
    }

    protected function computeVisualBreakdown(array $queryVec, array $targetVec, string $modelVersion, float $overall): array
    {
        if (in_array($modelVersion, ['php-gd-v2', 'php-gd-v3'], true) && count($queryVec) === 128 && count($targetVec) === 128) {
            $colorSim = $this->cosineSimilarity(array_slice($queryVec, 0, 48), array_slice($targetVec, 0, 48));
            $shapeSim = $this->cosineSimilarity(array_slice($queryVec, 48, 16), array_slice($targetVec, 48, 16));
            $layoutSim = $this->cosineSimilarity(array_slice($queryVec, 64, 64), array_slice($targetVec, 64, 64));

            return [
                'engine' => $modelVersion,
                'color' => (int) round($colorSim * 100),
                'shape' => (int) round($shapeSim * 100),
                'layout' => (int) round($layoutSim * 100),
            ];
        }

        return [
            'engine' => $modelVersion,
            'semantic' => (int) round($overall * 100),
        ];
    }
}
