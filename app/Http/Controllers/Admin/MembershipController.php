<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MembershipStatus;
use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMembershipStatusRequest;
use App\Models\OrganizationMembership;
use App\Services\Organization\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function __construct(public InvitationService $invitationService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OrganizationMembership::class);

        $user = $request->user();

        $query = OrganizationMembership::query()
            ->with(['user', 'organization', 'role'])
            ->latest();

        if (! $user->isPlatformSuperAdmin()) {
            $query->where('organization_id', $user->organizationId());
        }

        $memberships = $query->paginate(20);

        return view('admin.memberships.index', compact('memberships'));
    }

    public function update(UpdateMembershipStatusRequest $request, OrganizationMembership $membership): RedirectResponse
    {

        $actor = $request->user();

        try {
            $this->invitationService->updateMembershipStatus(
                membership: $membership,
                status: MembershipStatus::from($request->validated('status')),
                actor: $actor,
                request: $request,
            );
        } catch (InvitationException $exception) {
            return redirect()->route('admin.memberships.index')->withErrors(['membership' => $exception->getMessage()]);
        }

        return redirect()->route('admin.memberships.index')->with('status', __('Membership status updated.'));
    }
}
