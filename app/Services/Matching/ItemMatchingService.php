<?php

namespace App\Services\Matching;

use App\Contracts\Matching\ImageSimilarityContract;
use App\Enums\ItemStatus;
use App\Enums\MatchStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\ItemMatch;
use App\Models\ItemReport;
use App\Models\User;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ItemMatchingService
{
    public const ALGORITHM_VERSION = 'meta-v1';

    private const SYNONYMS = [
        'phone' => ['cellphone', 'mobile', 'smartphone', 'iphone', 'android', 'handset'],
        'wallet' => ['purse', 'billfold', 'cardholder', 'pitaka'],
        'glasses' => ['sunglasses', 'eyeglasses', 'eyewear', 'shades', 'salamin'],
        'watch' => ['smartwatch', 'timepiece', 'relo'],
        'earbuds' => ['airpods', 'earphones', 'headphones', 'buds'],
        'bag' => ['backpack', 'drybag', 'tote', 'sling', 'pouch', 'knapsack'],
        'camera' => ['gopro', 'actioncam', 'dslr', 'kamera'],
        'key' => ['keys', 'keycard', 'keyring', 'susi'],
        'passport' => ['pasaporte', 'traveldoc', 'id'],
    ];

    private const COLOR_FAMILIES = [
        'navy' => 'blue',
        'sky' => 'blue',
        'cyan' => 'blue',
        'teal' => 'blue',
        'turquoise' => 'blue',
        'indigo' => 'blue',
        'blue' => 'blue',
        'black' => 'black',
        'charcoal' => 'gray',
        'grey' => 'gray',
        'gray' => 'gray',
        'silver' => 'gray',
        'titanium' => 'gray',
        'white' => 'white',
        'cream' => 'white',
        'ivory' => 'white',
        'brown' => 'brown',
        'tan' => 'brown',
        'beige' => 'brown',
        'khaki' => 'brown',
        'red' => 'red',
        'maroon' => 'red',
        'crimson' => 'red',
        'pink' => 'red',
        'yellow' => 'yellow',
        'gold' => 'yellow',
        'amber' => 'yellow',
        'orange' => 'orange',
        'coral' => 'orange',
        'green' => 'green',
        'olive' => 'green',
        'mint' => 'green',
        'emerald' => 'green',
        'purple' => 'purple',
        'violet' => 'purple',
    ];

    private const STOP_WORDS = [
        'the', 'and', 'for', 'with', 'near', 'from', 'that', 'this', 'have', 'was', 'were',
        'are', 'its', 'has', 'had', 'into', 'onto', 'over', 'after', 'while', 'very',
        'ang', 'mga', 'sa', 'ng', 'na', 'at', 'ay', 'ito', 'niya', 'nito', 'may', 'para',
    ];

    public function __construct(
        public MasterDataCacheService $masterData,
        public ImageSimilarityContract $imageSimilarity,
        public NotificationDispatcher $notifications,
    ) {}

    public function matchForReport(ItemReport $report, bool $includePending = false): Collection
    {
        if (! $includePending && $report->verification_status !== VerificationStatus::Verified) {
            return collect();
        }

        $isLost = $report->type === ReportType::Lost;
        $eligibleSelf = $isLost
            ? $report->item_status === ItemStatus::Open
            : in_array($report->item_status, [ItemStatus::AwaitingTurnover, ItemStatus::InCustody, ItemStatus::ClaimPending], true);

        if (! $eligibleSelf) {
            return collect();
        }

        $report->loadMissing(['category', 'barangay', 'location']);

        $minScore = (float) $this->masterData->setting('match.min_score', 0.55);
        $beforeDays = (int) $this->masterData->setting('match.date_window_before_days', 2);
        $afterDays = (int) $this->masterData->setting('match.date_window_after_days', 90);
        $weights = $this->resolveWeights();

        $candidates = $this->fetchCandidates($report, $beforeDays, $afterDays, $includePending);
        if ($candidates->isEmpty()) {
            return collect();
        }

        if ($this->imageSimilarity instanceof ClipImageSimilarityProvider && $this->imageSimilarity->isEnabled()) {
            $this->imageSimilarity->preloadForReportIds(
                array_merge([$report->id], $candidates->pluck('id')->map(fn ($id): int => (int) $id)->all())
            );
        }

        $existingMatches = ItemMatch::query()
            ->where($isLost ? 'lost_report_id' : 'found_report_id', $report->id)
            ->whereIn($isLost ? 'found_report_id' : 'lost_report_id', $candidates->pluck('id')->all())
            ->get()
            ->keyBy(fn (ItemMatch $m): int => (int) ($isLost ? $m->found_report_id : $m->lost_report_id));

        $stored = collect();

        foreach ($candidates as $candidate) {
            $lost = $isLost ? $report : $candidate;
            $found = $isLost ? $candidate : $report;

            $evaluation = $this->scorePair($lost, $found, $weights, $beforeDays, $afterDays);

            if ($evaluation['score'] < $minScore) {
                continue;
            }

            $existing = $existingMatches->get((int) $candidate->id);

            if ($existing !== null && $existing->status !== MatchStatus::Suggested) {
                $stored->push($existing);

                continue;
            }

            $match = ItemMatch::query()->updateOrCreate(
                [
                    'lost_report_id' => $lost->id,
                    'found_report_id' => $found->id,
                ],
                [
                    'score' => round($evaluation['score'], 4),
                    'factors' => $evaluation['factors'],
                    'algorithm_version' => $evaluation['algorithm_version'],
                    'status' => MatchStatus::Suggested,
                ]
            );

            $stored->push($match);

            if (
                $existing === null
                && $found->is_public
                && $found->verification_status === VerificationStatus::Verified
            ) {
                $lost->loadMissing('reporter');

                if ($lost->reporter instanceof User) {
                    $scorePercent = (int) round($evaluation['score'] * 100);
                    $this->notifications->send(
                        recipient: $lost->reporter,
                        eventKey: 'match.identified',
                        title: __('Possible Similar Item Found (:ref)', ['ref' => $lost->reference_no]),
                        body: __('A similar found item (":found", :percent% match) was detected for your lost report ":lost". Review the found item and submit an ownership claim if it is yours.', [
                            'found' => $found->item_name,
                            'percent' => $scorePercent,
                            'lost' => $lost->item_name,
                        ]),
                        actionUrl: route('public.items.show', $found->reference_no),
                        meta: [
                            'match_id' => $match->id,
                            'lost_report_id' => $lost->id,
                            'found_report_id' => $found->id,
                            'score' => $scorePercent,
                        ],
                    );
                }
            }
        }

        return $stored->sortByDesc('score')->values();
    }

    public function scorePair(
        ItemReport $lost,
        ItemReport $found,
        ?array $weights = null,
        ?int $beforeDays = null,
        ?int $afterDays = null,
    ): array {
        $weights ??= $this->resolveWeights();
        $beforeDays ??= (int) $this->masterData->setting('match.date_window_before_days', 2);
        $afterDays ??= (int) $this->masterData->setting('match.date_window_after_days', 90);

        $factors = [];
        $weightedSum = 0.0;
        $availableWeight = 0.0;

        $addFactor = function (string $key, ?float $value) use (&$factors, &$weightedSum, &$availableWeight, $weights): void {
            if ($value === null) {
                return;
            }

            $weight = (float) ($weights[$key] ?? 0.0);
            if ($weight <= 0.0) {
                return;
            }

            $clamped = max(0.0, min(1.0, $value));
            $factors[$key] = round($clamped, 4);
            $weightedSum += $weight * $clamped;
            $availableWeight += $weight;
        };

        $addFactor('category', $this->scoreCategory($lost, $found));
        $addFactor('item_name', $this->scoreTokens($lost->item_name, $found->item_name, true));
        $addFactor('description', $this->scoreCosine(
            trim(($lost->description ?? '').' '.($lost->distinguishing_features ?? '')),
            trim(($found->description ?? '').' '.($found->distinguishing_features ?? ''))
        ));
        $addFactor('brand', $this->scoreFuzzyString($lost->brand, $found->brand));
        $addFactor('model', $this->scoreFuzzyString($lost->model, $found->model));
        $addFactor('color', $this->scoreColor($lost->color, $found->color));
        $addFactor('location', $this->scoreLocation($lost, $found));
        $addFactor('date', $this->scoreDate($lost, $found, $beforeDays, $afterDays));

        $metaScore = $availableWeight > 0.0 ? ($weightedSum / $availableWeight) : 0.0;
        if ($availableWeight < 0.5) {
            $metaScore *= $availableWeight;
        }

        $finalScore = $metaScore;
        $version = self::ALGORITHM_VERSION;

        if ($this->imageSimilarity->isEnabled()) {
            $imgScore = $this->imageSimilarity->similarity($lost, $found);
            if ($imgScore !== null) {
                $alpha = 0.25;
                $factors['image'] = round($imgScore, 4);
                $finalScore = ((1.0 - $alpha) * $metaScore) + ($alpha * $imgScore);
                $version = self::ALGORITHM_VERSION.'+img:'.$this->imageSimilarity->modelVersion();
            }
        }

        return [
            'score' => max(0.0, min(1.0, $finalScore)),
            'factors' => $factors,
            'algorithm_version' => Str::limit($version, 100, ''),
        ];
    }

    protected function fetchCandidates(ItemReport $report, int $beforeDays, int $afterDays, bool $includePending = false): Collection
    {
        $isLost = $report->type === ReportType::Lost;
        $targetType = $isLost ? ReportType::Found : ReportType::Lost;
        $targetStatuses = $isLost
            ? [ItemStatus::AwaitingTurnover->value, ItemStatus::InCustody->value, ItemStatus::ClaimPending->value]
            : [ItemStatus::Open->value];

        $compatibleCategories = $this->compatibleCategoryIds($report->category);

        $query = ItemReport::query()
            ->with(['category', 'barangay', 'location'])
            ->where('type', $targetType)
            ->whereIn('item_status', $targetStatuses)
            ->whereKeyNot($report->id);

        if ($includePending) {
            $query->whereIn('verification_status', [VerificationStatus::Verified->value, VerificationStatus::Pending->value]);
        } else {
            $query->where('verification_status', VerificationStatus::Verified);
        }

        if ($compatibleCategories !== []) {
            $query->whereIn('category_id', $compatibleCategories);
        }

        if ($report->occurred_date !== null) {
            if ($isLost) {
                $query->whereBetween('occurred_date', [
                    $report->occurred_date->copy()->subDays($beforeDays)->toDateString(),
                    $report->occurred_date->copy()->addDays($afterDays)->toDateString(),
                ]);
            } else {
                $query->whereBetween('occurred_date', [
                    $report->occurred_date->copy()->subDays($afterDays)->toDateString(),
                    $report->occurred_date->copy()->addDays($beforeDays)->toDateString(),
                ]);
            }
        }

        return $query->latest('occurred_date')->limit(100)->get();
    }

    protected function compatibleCategoryIds(?Category $category): array
    {
        if ($category === null) {
            return [];
        }

        $rootId = $category->parent_id ?? $category->id;
        $othersId = Category::query()->where('slug', 'others')->value('id');

        $ids = Category::query()
            ->where('id', $rootId)
            ->orWhere('parent_id', $rootId)
            ->pluck('id')
            ->all();

        if ($othersId !== null) {
            $ids[] = (int) $othersId;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function scoreCategory(ItemReport $lost, ItemReport $found): float
    {
        if ($lost->category_id === $found->category_id) {
            return 1.0;
        }

        $lostParent = $lost->category?->parent_id ?? $lost->category_id;
        $foundParent = $found->category?->parent_id ?? $found->category_id;

        return $lostParent === $foundParent ? 0.5 : 0.0;
    }

    protected function scoreTokens(?string $a, ?string $b, bool $expandSynonyms = false): ?float
    {
        $tokensA = $this->tokenize($a, $expandSynonyms);
        $tokensB = $this->tokenize($b, $expandSynonyms);

        if ($tokensA === [] || $tokensB === []) {
            return null;
        }

        $intersection = array_intersect($tokensA, $tokensB);
        $union = array_unique(array_merge($tokensA, $tokensB));

        return count($union) > 0 ? count($intersection) / count($union) : 0.0;
    }

    protected function scoreCosine(?string $a, ?string $b): ?float
    {
        $tokensA = $this->tokenize($a, true);
        $tokensB = $this->tokenize($b, true);

        if ($tokensA === [] || $tokensB === []) {
            return null;
        }

        $vecA = array_count_values($tokensA);
        $vecB = array_count_values($tokensB);

        $dot = 0.0;
        foreach ($vecA as $term => $countA) {
            if (isset($vecB[$term])) {
                $dot += $countA * $vecB[$term];
            }
        }

        $magA = sqrt(array_sum(array_map(fn (int $c): int => $c * $c, $vecA)));
        $magB = sqrt(array_sum(array_map(fn (int $c): int => $c * $c, $vecB)));

        return ($magA > 0.0 && $magB > 0.0) ? ($dot / ($magA * $magB)) : 0.0;
    }

    protected function scoreFuzzyString(?string $a, ?string $b): ?float
    {
        $normA = $this->normalizePlain($a);
        $normB = $this->normalizePlain($b);

        if ($normA === '' || $normB === '') {
            return null;
        }

        if ($normA === $normB) {
            return 1.0;
        }

        $maxLen = max(strlen($normA), strlen($normB));
        $distance = levenshtein($normA, $normB);
        $ratio = 1.0 - ($distance / $maxLen);

        return $ratio >= 0.8 ? $ratio : 0.0;
    }

    protected function scoreColor(?string $a, ?string $b): ?float
    {
        $normA = $this->normalizePlain($a);
        $normB = $this->normalizePlain($b);

        if ($normA === '' || $normB === '') {
            return null;
        }

        if ($normA === $normB) {
            return 1.0;
        }

        $familyA = $this->colorFamily($normA);
        $familyB = $this->colorFamily($normB);

        return ($familyA !== null && $familyA === $familyB) ? 0.6 : 0.0;
    }

    protected function colorFamily(string $color): ?string
    {
        foreach (explode(' ', $color) as $word) {
            if (isset(self::COLOR_FAMILIES[$word])) {
                return self::COLOR_FAMILIES[$word];
            }
        }

        return null;
    }

    protected function scoreLocation(ItemReport $lost, ItemReport $found): float
    {
        if ($lost->location_id !== null && $lost->location_id === $found->location_id) {
            return 1.0;
        }

        if ($lost->barangay_id === $found->barangay_id) {
            return 0.6;
        }

        return 0.2;
    }

    protected function scoreDate(ItemReport $lost, ItemReport $found, int $beforeDays, int $afterDays): ?float
    {
        if ($lost->occurred_date === null || $found->occurred_date === null) {
            return null;
        }

        $diffDays = $lost->occurred_date->diffInDays($found->occurred_date, false);

        if ($diffDays < -$beforeDays || $diffDays > $afterDays) {
            return 0.0;
        }

        $absDiff = abs($diffDays);
        if ($absDiff <= 1) {
            return 1.0;
        }

        $maxWindow = max(1, $diffDays >= 0 ? $afterDays : $beforeDays);

        return max(0.0, 1.0 - (($absDiff - 1) / $maxWindow));
    }

    protected function tokenize(?string $text, bool $expandSynonyms): array
    {
        $plain = $this->normalizePlain($text);
        if ($plain === '') {
            return [];
        }

        $raw = preg_split('/\s+/', $plain) ?: [];
        $tokens = [];

        foreach ($raw as $word) {
            if (strlen($word) < 2 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            if ($expandSynonyms) {
                $word = $this->canonicalSynonym($word);
            }

            $tokens[] = $word;
        }

        return $tokens;
    }

    protected function canonicalSynonym(string $word): string
    {
        foreach (self::SYNONYMS as $canonical => $variants) {
            if ($word === $canonical || in_array($word, $variants, true)) {
                return $canonical;
            }
        }

        return $word;
    }

    protected function normalizePlain(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $ascii = Str::ascii(mb_strtolower($text));
        $cleaned = preg_replace('/[^a-z0-9\s]+/', ' ', $ascii) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', $cleaned));
    }

    protected function resolveWeights(): array
    {
        $defaults = [
            'category' => 0.20,
            'item_name' => 0.15,
            'description' => 0.20,
            'brand' => 0.10,
            'model' => 0.05,
            'color' => 0.10,
            'location' => 0.10,
            'date' => 0.10,
        ];

        $configured = $this->masterData->setting('match.weights', $defaults);

        return is_array($configured) ? array_merge($defaults, $configured) : $defaults;
    }
}
