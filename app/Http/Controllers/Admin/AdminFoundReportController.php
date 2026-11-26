<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use App\Enums\VerificationStatus;
use App\Exceptions\ImageValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminFoundReportRequest;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Services\MasterData\MasterDataCacheService;
use App\Services\Matching\VisualSearchService;
use App\Services\Reports\FoundReportService;
use App\Services\Reports\ImageValidationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFoundReportController extends Controller
{
    public function __construct(
        public MasterDataCacheService $masterData,
        public FoundReportService $foundReportService,
        public ImageValidationClient $imageValidator,
        public VisualSearchService $visualSearch,
    ) {}

    public function create(Request $request): View
    {

        $user = $request->user();

        $categories = $this->masterData->categories();
        $barangays = $this->masterData->barangays();
        $locations = $this->masterData->locations();
        $organizations = $user->isPlatformSuperAdmin()
            ? $this->masterData->organizations()
            : Organization::query()->whereKey($user->organizationId())->get();

        return view('admin.reports.create-found', compact('categories', 'barangays', 'locations', 'organizations'));
    }

    public function precheck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required_without:photos', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photos' => ['required_without:photo', 'nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'keyword' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $uploadedPhotos = $request->hasFile('photos')
            ? array_values(array_filter((array) $request->file('photos')))
            : ($request->hasFile('photo') ? [$request->file('photo')] : []);

        $imageCheck = $this->imageValidator->validate($uploadedPhotos[0]);

        $matches = [];
        $seenIds = [];

        foreach ($this->visualSearch->searchLostItemsByPhotos($uploadedPhotos, 6) as $entry) {

            $lost = $entry['report'];
            $lost->loadMissing('images');
            $thumb = $lost->images->sortByDesc('is_primary')->first();
            $seenIds[$lost->id] = true;
            $matches[] = [
                'reference_no' => $lost->reference_no,
                'item_name' => $lost->item_name,
                'category' => $lost->category?->label(),
                'barangay' => $lost->barangay?->name,
                'occurred_date' => $lost->occurred_date?->format('M d, Y'),
                'score' => (int) round($entry['similarity'] * 100),
                'match_type' => 'visual',
                'image_url' => $thumb ? route('admin.images.preview', $thumb) : null,
                'url' => route('admin.reports.show', $lost),
            ];
        }

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $categoryId = ! empty($validated['category_id']) ? (int) $validated['category_id'] : null;

        if ($keyword !== '' || $categoryId !== null) {
            $query = ItemReport::query()
                ->with(['category', 'barangay', 'images'])
                ->where('type', ReportType::Lost)
                ->where('verification_status', VerificationStatus::Verified)
                ->where('item_status', ItemStatus::Open)
                ->latest('occurred_date');

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            if ($keyword !== '') {
                $like = '%'.strtolower($keyword).'%';
                $query->where(function ($builder) use ($like): void {
                    $builder->whereRaw('LOWER(item_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(search_text) LIKE ?', [$like]);
                });
            }

            foreach ($query->limit(6)->get() as $lost) {
                if (isset($seenIds[$lost->id])) {
                    continue;
                }
                $seenIds[$lost->id] = true;
                $thumb = $lost->images->sortByDesc('is_primary')->first();
                $matches[] = [
                    'reference_no' => $lost->reference_no,
                    'item_name' => $lost->item_name,
                    'category' => $lost->category?->label(),
                    'barangay' => $lost->barangay?->name,
                    'occurred_date' => $lost->occurred_date?->format('M d, Y'),
                    'score' => null,
                    'match_type' => 'metadata',
                    'image_url' => $thumb ? route('admin.images.preview', $thumb) : null,
                    'url' => route('admin.reports.show', $lost),
                ];
            }
        }

        return response()->json([
            'image_check' => [
                'status' => $imageCheck['status']->value,
                'messages' => $imageCheck['messages'],
            ],
            'matches' => $matches,
            'has_matches' => $matches !== [],
        ]);
    }

    public function store(StoreAdminFoundReportRequest $request): RedirectResponse
    {

        $user = $request->user();
        $validated = $request->validated();

        if (! $user->isPlatformSuperAdmin()) {
            $validated['custody_organization_id'] = $user->organizationId();
        }

        $photos = $request->file('photos', []);

        try {
            $report = $this->foundReportService->submit(
                actor: $user,
                data: $validated,
                photos: is_array($photos) ? $photos : [],
                inInstitutionalCustody: true,
                request: $request,
            );
        } catch (ImageValidationException $exception) {
            return back()->withInput()->withErrors(['photos' => $exception->messages[0] ?? $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', __('Found item :ref registered in institutional custody.', ['ref' => $report->reference_no]));
    }
}
