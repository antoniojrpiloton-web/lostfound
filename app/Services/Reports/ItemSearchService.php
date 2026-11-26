<?php

namespace App\Services\Reports;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Models\Category;
use App\Models\ItemReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ItemSearchService
{
    public function searchPublic(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = ItemReport::query()
            ->publiclyVisible()
            ->with(['category.parent', 'barangay', 'location', 'handlingOrganization', 'custodyOrganization', 'images']);

        $rawKeyword = trim((string) ($filters['q'] ?? ''));
        $normalizedTerms = $this->extractNormalizedTerms($rawKeyword);
        $booleanQuery = $this->buildBooleanFullTextQuery($normalizedTerms);

        if ($normalizedTerms !== []) {
            $query->where(function (Builder $builder) use ($booleanQuery, $normalizedTerms): void {
                if ($booleanQuery !== '') {
                    $builder->whereRaw('MATCH(search_text) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
                }

                $shortTerms = array_filter($normalizedTerms, fn (string $term): bool => strlen($term) < 3);
                foreach ($shortTerms as $shortTerm) {
                    $builder->where('search_text', 'like', '%'.$shortTerm.'%');
                }
            });
        }

        if (! empty($filters['type']) && in_array((string) $filters['type'], ReportType::values(), true)) {
            $query->where('type', (string) $filters['type']);
        }

        if (! empty($filters['category_id'])) {
            $categoryId = (int) $filters['category_id'];
            $childIds = Category::query()->where('parent_id', $categoryId)->pluck('id')->all();
            $query->whereIn('category_id', array_merge([$categoryId], $childIds));
        }

        if (! empty($filters['barangay_id'])) {
            $query->where('barangay_id', (int) $filters['barangay_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', (int) $filters['location_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('occurred_date', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('occurred_date', '<=', (string) $filters['date_to']);
        }

        if (! empty($filters['public_status'])) {
            match ((string) $filters['public_status']) {
                'lost' => $query->where('type', ReportType::Lost)->where('item_status', ItemStatus::Open),
                'found' => $query->where('type', ReportType::Found)->where('item_status', ItemStatus::InCustody),
                'claim_pending' => $query->where('type', ReportType::Found)->where('item_status', ItemStatus::ClaimPending),
                'claimed' => $query->whereIn('item_status', [ItemStatus::Returned->value, ItemStatus::Resolved->value]),
                default => null,
            };
        }

        $sort = (string) ($filters['sort'] ?? ($booleanQuery !== '' ? 'relevance' : 'newest'));

        if ($sort === 'relevance' && $booleanQuery !== '') {
            $nameLike = '%'.strtolower($rawKeyword).'%';
            $query->select('item_reports.*')
                ->selectRaw(
                    '(MATCH(search_text) AGAINST(? IN BOOLEAN MODE) + CASE WHEN LOWER(item_name) LIKE ? THEN 2.0 ELSE 0 END) as relevance_score',
                    [$booleanQuery, $nameLike]
                )
                ->orderByDesc('relevance_score')
                ->orderByDesc('verified_at');
        } else {
            match ($sort) {
                'oldest' => $query->orderBy('verified_at')->orderBy('id'),
                'date_desc' => $query->orderByDesc('occurred_date')->orderByDesc('id'),
                'date_asc' => $query->orderBy('occurred_date')->orderBy('id'),
                default => $query->orderByDesc('verified_at')->orderByDesc('id'),
            };
        }

        return $query->paginate($perPage)->withQueryString();
    }

    protected function extractNormalizedTerms(string $keyword): array
    {
        if ($keyword === '') {
            return [];
        }

        $ascii = Str::ascii(mb_strtolower($keyword));
        $cleaned = preg_replace('/[^a-z0-9\s]+/', ' ', $ascii) ?? '';
        $parts = preg_split('/\s+/', trim($cleaned)) ?: [];

        return array_values(array_unique(array_filter($parts, fn (string $term): bool => $term !== '')));
    }

    protected function buildBooleanFullTextQuery(array $terms): string
    {
        $longTerms = array_filter($terms, fn (string $term): bool => strlen($term) >= 3);

        if ($longTerms === []) {
            return '';
        }

        return implode(' ', array_map(fn (string $term): string => '+'.$term.'*', $longTerms));
    }
}
