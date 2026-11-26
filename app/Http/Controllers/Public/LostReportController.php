<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReportType;
use App\Exceptions\ImageValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreLostReportRequest;
use App\Models\ItemReport;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Matching\VisualSearchService;
use App\Services\Reports\ImageValidationClient;
use App\Services\Reports\LostReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LostReportController extends Controller
{
    public function __construct(
        public MasterDataCacheService $masterData,
        public LostReportService $lostReportService,
        public ImageValidationClient $imageValidator,
        public VisualSearchService $visualSearch,
    ) {}

    public function create(): View
    {
        Gate::authorize('create', ItemReport::class);

        $categories = $this->masterData->categories();
        $barangays = $this->masterData->barangays();
        $locations = $this->masterData->locations();

        return view('public.reports.create-lost', compact('categories', 'barangays', 'locations'));
    }

    public function precheck(Request $request): JsonResponse
    {
        Gate::authorize('create', ItemReport::class);

        $validated = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'keyword' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $uploadedPhotos = $request->hasFile('photos')
            ? array_values(array_filter((array) $request->file('photos')))
            : ($request->hasFile('photo') ? [$request->file('photo')] : []);

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $categoryId = ! empty($validated['category_id']) ? (int) $validated['category_id'] : null;

        if ($uploadedPhotos === [] && $keyword === '' && $categoryId === null) {
            return response()->json([
                'message' => __('Please upload a photo, enter a keyword, or select a category to search first.'),
            ], 422);
        }

        $imageCheck = null;
        $matches = [];
        $seenIds = [];
        $visualEnabled = $this->visualSearch->isVisualSearchEnabled() || $this->visualSearch->isImageMatchingEnabled();

        if ($uploadedPhotos !== []) {
            $validation = $this->imageValidator->validate($uploadedPhotos[0]);
            $imageCheck = [
                'status' => $validation['status']->value,
                'messages' => $validation['messages'],
            ];

            if ($visualEnabled) {
                $visualOutcome = $this->visualSearch->searchPublicItemsByPhotos($uploadedPhotos, ReportType::Found->value, 6);
                foreach ($visualOutcome['results'] as $entry) {

                    $found = $entry['report'];
                    $found->loadMissing('images');
                    $thumb = $found->images->filter(fn ($img): bool => $img->public_path !== null)->sortByDesc('is_primary')->first();
                    $seenIds[$found->id] = true;
                    $matches[] = [
                        'reference_no' => $found->reference_no,
                        'item_name' => $found->item_name,
                        'category' => $found->category?->label(),
                        'barangay' => $found->barangay?->name,
                        'occurred_date' => $found->occurred_date?->format('M d, Y'),
                        'score' => (int) round($entry['similarity'] * 100),
                        'match_type' => 'visual',
                        'image_url' => $thumb ? route('public.items.image', [$found->reference_no, $thumb]) : null,
                        'url' => route('public.items.show', $found->reference_no),
                        'claim_url' => route('claims.create', $found->reference_no),
                    ];
                }
            }
        }

        if ($keyword !== '' || $categoryId !== null) {
            $query = ItemReport::query()
                ->publiclyVisible()
                ->with(['category', 'barangay', 'images'])
                ->where('type', ReportType::Found)
                ->latest('occurred_date');

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            if ($keyword !== '') {
                $terms = array_values(array_filter(preg_split('/\s+/', strtolower($keyword)) ?: []));
                $query->where(function ($builder) use ($terms): void {
                    foreach ($terms as $term) {
                        $like = '%'.$term.'%';
                        $builder->orWhereRaw('LOWER(item_name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(search_text) LIKE ?', [$like]);
                    }
                });
            }

            foreach ($query->limit(6)->get() as $found) {
                if (isset($seenIds[$found->id])) {
                    continue;
                }
                $seenIds[$found->id] = true;
                $thumb = $found->images->filter(fn ($img): bool => $img->public_path !== null)->sortByDesc('is_primary')->first();
                $matches[] = [
                    'reference_no' => $found->reference_no,
                    'item_name' => $found->item_name,
                    'category' => $found->category?->label(),
                    'barangay' => $found->barangay?->name,
                    'occurred_date' => $found->occurred_date?->format('M d, Y'),
                    'score' => null,
                    'match_type' => 'metadata',
                    'image_url' => $thumb ? route('public.items.image', [$found->reference_no, $thumb]) : null,
                    'url' => route('public.items.show', $found->reference_no),
                    'claim_url' => route('claims.create', $found->reference_no),
                ];
            }
        }

        return response()->json([
            'image_check' => $imageCheck,
            'visual_search_enabled' => $visualEnabled,
            'matches' => $matches,
            'has_matches' => $matches !== [],
        ]);
    }

    public function store(StoreLostReportRequest $request): RedirectResponse
    {

        $user = $request->user();
        $photos = $request->file('photos', []);

        try {
            $report = $this->lostReportService->submit(
                reporter: $user,
                data: $request->validated(),
                photos: is_array($photos) ? $photos : [],
                request: $request,
            );
        } catch (ImageValidationException $exception) {
            return back()->withInput()->withErrors(['photos' => $exception->messages[0] ?? $exception->getMessage()]);
        }

        return redirect()
            ->route('reports.show_own', $report)
            ->with('status', __('Lost item report :ref has been submitted for verification.', ['ref' => $report->reference_no]));
    }
}
