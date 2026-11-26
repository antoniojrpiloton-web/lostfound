<?php

namespace App\Http\Controllers\Public;

use App\Enums\ImageValidationStatus;
use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\User;
use App\Services\Matching\VisualSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SmartSearchController extends Controller
{
    public function __construct(public VisualSearchService $visualSearch) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', 'in:lost,found'],
            'scope' => ['nullable', 'in:public,org'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $isInstitutional = $user instanceof User && $user->isInstitutionalUser();
        $useOrgScope = $isInstitutional && ($validated['scope'] ?? 'public') === 'org';
        $type = $validated['type'] ?? null;

        $uploadedPhotos = [];
        if ($request->hasFile('photos')) {
            $uploadedPhotos = array_values(array_filter((array) $request->file('photos')));
        } elseif ($request->hasFile('photo')) {
            $uploadedPhotos = [$request->file('photo')];
        }

        if ($uploadedPhotos !== []) {
            if (! $this->visualSearch->isVisualSearchEnabled()) {
                return response()->json([
                    'items' => [],
                    'error' => __('Visual photo search is currently disabled in System Settings.'),
                ], 403);
            }

            $outcome = $this->visualSearch->searchPublicItemsByPhotos(
                $uploadedPhotos,
                $type,
                12,
                $useOrgScope && $user instanceof User ? $user : null,
            );

            $items = $outcome['results']->map(function (array $entry) use ($useOrgScope): array {

                $report = $entry['report'];
                $report->loadMissing('images');

                return $this->formatItem(
                    $report,
                    (int) round($entry['similarity'] * 100),
                    $useOrgScope,
                    $entry['breakdown'] ?? null,
                );
            })->values()->all();

            return response()->json([
                'mode' => 'visual',
                'items' => $items,
                'error' => $outcome['error'],
            ]);
        }

        $keyword = trim((string) ($validated['q'] ?? ''));
        if (mb_strlen($keyword) < 2) {
            return response()->json(['mode' => 'text', 'items' => []]);
        }

        $query = ItemReport::query()->with(['category', 'barangay', 'images'])->latest('occurred_date');

        if ($useOrgScope && $user instanceof User) {
            $query->visibleTo($user);
        } else {
            $query->publiclyVisible();
        }

        if ($type !== null && in_array($type, ReportType::values(), true)) {
            $query->where('type', $type);
        }

        $terms = array_values(array_filter(preg_split('/\s+/', strtolower($keyword)) ?: []));
        $query->where(function ($builder) use ($terms): void {
            foreach ($terms as $term) {
                $like = '%'.$term.'%';
                $builder->orWhereRaw('LOWER(reference_no) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(item_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(search_text) LIKE ?', [$like]);
            }
        });

        $items = $query->limit(10)->get()->map(
            fn (ItemReport $report): array => $this->formatItem($report, null, $useOrgScope)
        )->values()->all();

        return response()->json([
            'mode' => 'text',
            'items' => $items,
        ]);
    }

    protected function formatItem(ItemReport $report, ?int $score, bool $adminLink, ?array $breakdown = null): array
    {
        $imageUrl = $this->resolveThumbnailUrl($report, $adminLink);
        $canClaim = ! $adminLink
            && $report->type === ReportType::Found
            && in_array($report->item_status, [ItemStatus::InCustody, ItemStatus::ClaimPending], true);

        return [
            'reference_no' => $report->reference_no,
            'item_name' => $report->item_name,
            'description' => Str::limit((string) $report->description, 95),
            'type' => $report->type->value,
            'type_label' => ucfirst($report->type->value),
            'category' => $report->category?->label(),
            'barangay' => $report->barangay?->name,
            'occurred_date' => $report->occurred_date?->format('M d, Y'),
            'status_label' => $adminLink ? ucfirst(str_replace('_', ' ', $report->verification_status->value)) : $report->publicStatusLabel(),
            'score' => $score,
            'breakdown' => $breakdown,
            'image_url' => $imageUrl,
            'url' => $adminLink ? route('admin.reports.show', $report) : route('public.items.show', $report->reference_no),
            'claim_url' => $canClaim ? route('claims.create', $report->reference_no) : null,
        ];
    }

    protected function resolveThumbnailUrl(ItemReport $report, bool $adminLink): ?string
    {
        $images = $report->images;
        if ($images->isEmpty()) {
            return null;
        }

        if (! $adminLink) {

            $publicImg = $images
                ->filter(fn (ItemImage $img): bool => $img->public_path !== null
                    && in_array($img->validation_status, [ImageValidationStatus::Passed, ImageValidationStatus::Overridden], true))
                ->sortByDesc('is_primary')
                ->first();

            return $publicImg !== null ? route('public.items.image', [$report->reference_no, $publicImg]) : null;
        }

        $primary = $images->sortByDesc('is_primary')->first();
        if (! $primary instanceof ItemImage) {
            return null;
        }

        if ($primary->public_path !== null && $report->is_public) {
            return route('public.items.image', [$report->reference_no, $primary]);
        }

        return route('admin.images.preview', $primary);
    }
}
