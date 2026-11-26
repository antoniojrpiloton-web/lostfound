<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\ImageValidationException;
use App\Http\Controllers\Controller;
use App\Models\ItemImage;
use App\Models\ItemReport;
use App\Services\Reports\OfficialPdfService;
use App\Services\Reports\ReportImageService;
use App\Services\Reports\ReportVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyReportsController extends Controller
{
    public function __construct(
        public ReportImageService $imageService,
        public ReportVerificationService $verificationService,
        public OfficialPdfService $officialPdf,
    ) {}

    public function index(Request $request): View
    {

        $user = $request->user();

        $reports = ItemReport::query()
            ->with(['category', 'barangay', 'location', 'handlingOrganization'])
            ->where('reporter_user_id', $user->id)
            ->latest()
            ->paginate(12);

        return view('public.reports.mine', compact('reports'));
    }

    public function show(ItemReport $report): View
    {
        Gate::authorize('viewOwn', $report);

        $report->load([
            'category',
            'barangay',
            'location',
            'handlingOrganization',
            'images',
            'statusHistories' => fn ($query) => $query->with('changer')->latest('created_at'),
        ]);

        return view('public.reports.show-own', compact('report'));
    }

    public function referenceSlip(ItemReport $report): Response
    {
        Gate::authorize('viewOwn', $report);
        $report->load(['category', 'location', 'handlingOrganization', 'custodyOrganization']);

        $lookupUrl = $report->is_public
            ? route('public.items.show', $report->reference_no)
            : route('reports.show_own', $report);

        $pdfBytes = $this->officialPdf->buildReferenceSlip($report, $lookupUrl);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reference-slip-'.$report->reference_no.'.pdf"',
        ]);
    }

    public function showImage(ItemReport $report, ItemImage $image): StreamedResponse
    {
        Gate::authorize('viewOwn', $report);

        if ($image->item_report_id !== $report->id || ! Storage::disk('local')->exists($image->private_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($image->private_path, basename($image->private_path), [
            'Content-Type' => $image->mime,
            'Content-Disposition' => 'inline',
        ]);
    }

    public function replaceImage(Request $request, ItemReport $report, ItemImage $image): RedirectResponse
    {
        Gate::authorize('viewOwn', $report);

        if ($image->item_report_id !== $report->id) {
            abort(404);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $this->imageService->replaceImage($image, $validated['photo']);
        } catch (ImageValidationException $exception) {
            return redirect()
                ->route('reports.show_own', $report)
                ->withErrors(['photo' => $exception->messages[0] ?? $exception->getMessage()]);
        }

        return redirect()
            ->route('reports.show_own', $report)
            ->with('status', __('Replacement photo uploaded successfully.'));
    }

    public function withdraw(Request $request, ItemReport $report): RedirectResponse
    {
        Gate::authorize('viewOwn', $report);

        $user = $request->user();

        try {
            $this->verificationService->withdraw($report, $user, $request->string('remarks')->trim()->toString() ?: null, $request);
        } catch (RuntimeException $exception) {
            return redirect()->route('reports.show_own', $report)->withErrors(['report' => $exception->getMessage()]);
        }

        return redirect()->route('reports.show_own', $report)->with('status', __('Your report has been withdrawn.'));
    }
}
