<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ImageValidationStatus;
use App\Http\Controllers\Controller;
use App\Models\ItemImage;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageReviewController extends Controller
{
    public function __construct(public AuditService $auditService) {}

    public function index(Request $request): View
    {

        $user = $request->user();

        $query = ItemImage::query()
            ->with(['itemReport.barangay', 'itemReport.category', 'itemReport.handlingOrganization'])
            ->where('validation_status', ImageValidationStatus::Review)
            ->latest();

        if (! $user->isPlatformSuperAdmin()) {
            $orgId = $user->organizationId();
            $query->whereHas('itemReport', function ($q) use ($orgId): void {
                $q->where('handling_organization_id', $orgId)
                    ->orWhere('custody_organization_id', $orgId);
            });
        }

        $images = $query->paginate(15);

        return view('admin.images.reviews', compact('images'));
    }

    public function preview(ItemImage $image): StreamedResponse
    {
        $report = $image->itemReport()->firstOrFail();
        Gate::authorize('viewInstitutional', $report);

        if (! Storage::disk('local')->exists($image->private_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($image->private_path, basename($image->private_path), [
            'Content-Type' => $image->mime,
            'Content-Disposition' => 'inline',
        ]);
    }

    public function decide(Request $request, ItemImage $image): RedirectResponse
    {
        $report = $image->itemReport()->firstOrFail();
        Gate::authorize('verify', $report);

        $validated = $request->validate([
            'decision' => ['required', Rule::in([ImageValidationStatus::Overridden->value, ImageValidationStatus::Rejected->value])],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $image->validation_status->value;
        $newStatus = ImageValidationStatus::from($validated['decision']);
        $result = is_array($image->validation_result) ? $image->validation_result : [];
        $result['manual_review_remarks'] = $validated['remarks'] ?? null;

        $image->forceFill([
            'validation_status' => $newStatus,
            'validation_result' => $result,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        $this->auditService->record(
            action: 'image.manual_review_decided',
            auditable: $image,
            oldValues: ['validation_status' => $oldStatus],
            newValues: ['validation_status' => $newStatus->value, 'remarks' => $validated['remarks'] ?? null],
            actor: $request->user(),
            organizationId: $report->handling_organization_id,
            request: $request,
        );

        return redirect()
            ->route('admin.images.reviews')
            ->with('status', __('Image review decision recorded.'));
    }
}
