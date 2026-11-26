<?php

namespace App\Http\Controllers\Public;

use App\Enums\ImageValidationStatus;
use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Models\Location;
use App\Services\Localization\ContentTranslationService;
use App\Services\Matching\VisualSearchService;
use App\Services\Reports\FacebookPosterService;
use App\Services\Reports\ItemSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicItemController extends Controller
{
    public function __construct(
        public ItemSearchService $searchService,
        public VisualSearchService $visualSearch,
        public ContentTranslationService $contentTranslation,
        public FacebookPosterService $facebookPoster,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'type', 'category_id', 'barangay_id', 'location_id', 'date_from', 'date_to', 'public_status', 'sort']);
        $items = $this->searchService->searchPublic($filters, 12);
        $items->getCollection()->loadMissing('images');

        $selectedCategory = ! empty($filters['category_id']) ? Category::query()->with('parent')->find((int) $filters['category_id']) : null;
        $selectedBarangay = ! empty($filters['barangay_id']) ? Barangay::query()->find((int) $filters['barangay_id']) : null;
        $selectedLocation = ! empty($filters['location_id']) ? Location::query()->with('barangay')->find((int) $filters['location_id']) : null;
        $visualSearchEnabled = $this->visualSearch->isVisualSearchEnabled();

        return view('public.items.index', compact('items', 'filters', 'selectedCategory', 'selectedBarangay', 'selectedLocation', 'visualSearchEnabled'));
    }

    public function visualSearch(Request $request): View
    {
        if (! $this->visualSearch->isVisualSearchEnabled()) {
            abort(403, __('Visual photo search is currently disabled.'));
        }

        $validated = $request->validate([
            'photo' => ['required_without:photos', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photos' => ['required_without:photo', 'nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'type' => ['nullable', 'in:lost,found'],
        ]);

        $uploadedPhotos = $request->hasFile('photos')
            ? array_values(array_filter((array) $request->file('photos')))
            : ($request->hasFile('photo') ? [$request->file('photo')] : []);

        $scopeType = $validated['type'] ?? null;
        $outcome = $this->visualSearch->searchPublicItemsByPhotos($uploadedPhotos, $scopeType, 12);
        $outcome['results']->each(fn (array $entry) => $entry['report']->loadMissing('images'));

        return view('public.items.visual-results', [
            'results' => $outcome['results'],
            'error' => $outcome['error'],
            'scopeType' => $scopeType,
        ]);
    }

    public function show(string $referenceNo): View
    {
        $report = ItemReport::query()
            ->publiclyVisible()
            ->with(['category.parent', 'barangay', 'location', 'handlingOrganization', 'custodyOrganization', 'images'])
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        $publicImages = $report->images->filter(
            fn (ItemImage $image): bool => $image->public_path !== null
                && in_array($image->validation_status, [ImageValidationStatus::Passed, ImageValidationStatus::Overridden], true)
        );

        $descriptionTranslation = $this->contentTranslation->translateReportField($report, 'description');

        $similarItems = ItemReport::query()
            ->publiclyVisible()
            ->with(['category', 'barangay', 'images'])
            ->where('type', $report->type)
            ->where('category_id', $report->category_id)
            ->whereKeyNot($report->id)
            ->latest('occurred_date')
            ->limit(3)
            ->get();

        $fbShareUrl = route('public.items.show', $report->reference_no);
        $fbCaption = $this->facebookPoster->buildCaption($report, $fbShareUrl);

        return view('public.items.show', compact('report', 'publicImages', 'descriptionTranslation', 'similarItems', 'fbShareUrl', 'fbCaption'));
    }

    public function fbPoster(Request $request, string $referenceNo): Response
    {
        $report = ItemReport::query()
            ->publiclyVisible()
            ->with(['category', 'location', 'handlingOrganization', 'custodyOrganization', 'images'])
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        $pngBytes = $this->facebookPoster->buildPosterPng($report, allowPrivateImage: false);
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pngBytes, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => $disposition.'; filename="fb-poster-'.$report->reference_no.'.png"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function showImage(string $referenceNo, ItemImage $image): StreamedResponse
    {
        $report = ItemReport::query()
            ->publiclyVisible()
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        if (
            $image->item_report_id !== $report->id
            || $image->public_path === null
            || ! Storage::disk('public')->exists($image->public_path)
        ) {
            abort(404);
        }

        return Storage::disk('public')->response($image->public_path, basename($image->public_path), [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function apiSearch(Request $request): JsonResponse
    {
        $filters = $request->only(['q', 'type', 'category_id', 'barangay_id', 'location_id', 'date_from', 'date_to', 'public_status', 'sort']);
        $items = $this->searchService->searchPublic($filters, 10);

        return response()->json([
            'data' => $items->getCollection()->map(fn (ItemReport $report): array => [
                'reference_no' => $report->reference_no,
                'type' => $report->type->value,
                'item_name' => $report->item_name,
                'category' => $report->category?->label(),
                'barangay' => $report->barangay?->name,
                'location' => $report->location?->name,
                'occurred_date' => $report->occurred_date?->toDateString(),
                'public_status' => $report->publicStatusLabel(),
                'url' => route('public.items.show', $report->reference_no),
            ])->values()->all(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
