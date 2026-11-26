<?php

namespace App\Http\Controllers\Public;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Exceptions\ImageValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreFoundReportRequest;
use App\Models\ItemReport;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Matching\VisualSearchService;
use App\Services\Reports\FoundReportService;
use App\Services\Reports\ImageValidationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FoundReportController extends Controller
{
    public function __construct(
        public MasterDataCacheService $masterData,
        public FoundReportService $foundReportService,
        public VisualSearchService $visualSearch,
        public ImageValidationClient $imageValidator,
    ) {}

    public function create(): View
    {
        Gate::authorize('create', ItemReport::class);

        $categories = $this->masterData->categories();
        $barangays = $this->masterData->barangays();
        $locations = $this->masterData->locations();
        $organizations = $this->masterData->organizations();
        $visualSearchEnabled = $this->visualSearch->isVisualSearchEnabled();

        return view('public.reports.create-found', compact('categories', 'barangays', 'locations', 'organizations', 'visualSearchEnabled'));
    }

    public function precheck(Request $request): JsonResponse
    {
        Gate::authorize('create', ItemReport::class);

        $validated = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'keyword' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $uploadedPhotos = $request->hasFile('photos')
            ? array_values(array_filter((array) $request->file('photos')))
            : ($request->hasFile('photo') ? [$request->file('photo')] : []);

        $imageCheck = null;
        $visualMatches = collect();

        if ($uploadedPhotos !== []) {
            $validation = $this->imageValidator->validate($uploadedPhotos[0]);
            $imageCheck = [
                'status' => $validation['status']->value,
                'messages' => $validation['messages'],
            ];

            if ($this->visualSearch->isVisualSearchEnabled()) {
                $visualMatches = $this->visualSearch->searchLostItemsByPhotos($uploadedPhotos, 6);
            }
        }

        $matches = collect();
        $seenIds = [];

        foreach ($visualMatches as $entry) {

            $report = $entry['report'];
            $report->loadMissing(['category', 'barangay', 'images']);
            $primaryImage = $report->images->firstWhere('is_primary', true) ?? $report->images->first();
            $seenIds[] = $report->id;
            $matches->push([
                'reference_no' => $report->reference_no,
                'item_name' => $report->item_name,
                'category' => $report->category?->name,
                'barangay' => $report->barangay?->name,
                'occurred_date' => $report->occurred_date?->format('M d, Y'),
                'score' => (int) round(((float) $entry['similarity']) * 100),
                'image_url' => $primaryImage ? route('public.items.image', [$report->reference_no, $primaryImage]) : null,
                'url' => route('public.items.show', $report->reference_no),
            ]);
        }

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;

        if ($matches->count() < 6 && ($keyword !== '' || $categoryId !== null)) {
            $query = ItemReport::query()
                ->with(['category', 'barangay', 'images'])
                ->where('type', ReportType::Lost)
                ->where('verification_status', VerificationStatus::Verified)
                ->where('item_status', ItemStatus::Open)
                ->where('is_public', true)
                ->whereNotIn('id', $seenIds);

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            if ($keyword !== '') {
                $like = '%'.$keyword.'%';
                $query->where(function ($builder) use ($like): void {
                    $builder->where('item_name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('color', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            }

            foreach ($query->latest('occurred_date')->limit(6 - $matches->count())->get() as $report) {
                $primaryImage = $report->images->firstWhere('is_primary', true) ?? $report->images->first();
                $matches->push([
                    'reference_no' => $report->reference_no,
                    'item_name' => $report->item_name,
                    'category' => $report->category?->name,
                    'barangay' => $report->barangay?->name,
                    'occurred_date' => $report->occurred_date?->format('M d, Y'),
                    'score' => null,
                    'image_url' => $primaryImage ? route('public.items.image', [$report->reference_no, $primaryImage]) : null,
                    'url' => route('public.items.show', $report->reference_no),
                ]);
            }
        }

        return response()->json([
            'image_check' => $imageCheck,
            'visual_search_enabled' => $this->visualSearch->isVisualSearchEnabled(),
            'has_matches' => $matches->isNotEmpty(),
            'matches' => $matches->values()->all(),
        ]);
    }

    public function store(StoreFoundReportRequest $request): RedirectResponse
    {

        $user = $request->user();
        $photos = $request->file('photos', []);

        try {
            $report = $this->foundReportService->submit(
                actor: $user,
                data: $request->validated(),
                photos: is_array($photos) ? $photos : [],
                inInstitutionalCustody: false,
                request: $request,
            );
        } catch (ImageValidationException $exception) {
            return back()->withInput()->withErrors(['photos' => $exception->messages[0] ?? $exception->getMessage()]);
        }

        return redirect()
            ->route('reports.show_own', $report)
            ->with('status', __('Found item report :ref submitted. You may keep the item safely with you; Police/Admin will contact you to coordinate a supervised return at the booth once a match is found.', ['ref' => $report->reference_no]));
    }
}
