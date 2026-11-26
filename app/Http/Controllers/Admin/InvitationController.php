<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Role;
use App\Services\Organization\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class InvitationController extends Controller
{
    public function __construct(public InvitationService $invitationService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OrganizationInvitation::class);

        $user = $request->user();

        $query = OrganizationInvitation::query()
            ->with(['organization', 'role', 'inviter'])
            ->latest();

        if (! $user->isPlatformSuperAdmin()) {
            $query->where('organization_id', $user->organizationId());
        }

        $invitations = $query->paginate(20);
        $organizations = $user->isPlatformSuperAdmin()
            ? Organization::query()->where('is_active', true)->orderBy('name')->get()
            : Organization::query()->whereKey($user->organizationId())->get();

        return view('admin.invitations.index', compact('invitations', 'organizations'));
    }

    public function store(StoreInvitationRequest $request): RedirectResponse|JsonResponse
    {

        $actor = $request->user();
        $validated = $request->validated();

        $role = Role::query()->where('key', $validated['role_key'])->firstOrFail();

        $organization = null;
        if ($role->key !== 'platform_super_admin') {
            $orgId = $actor->isPlatformSuperAdmin()
                ? ($validated['organization_id'] ?? null)
                : $actor->organizationId();

            $organization = $orgId !== null ? Organization::query()->find($orgId) : null;
        }

        try {
            $result = $this->invitationService->createInvitation(
                actor: $actor,
                email: $validated['email'],
                role: $role,
                organization: $organization,
                expiresInDays: (int) ($validated['expires_in_days'] ?? 7),
                request: $request,
            );
        } catch (InvitationException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return redirect()->route('admin.invitations.index')->withErrors(['invitation' => $exception->getMessage()]);
        }

        $inviteUrl = route('invitations.accept', ['token' => $result['plain_token']]);
        $expiresDays = (int) ($validated['expires_in_days'] ?? 7);
        $deskName = $organization?->name ?? __('Boracay Lost and Found System');
        $emailSent = false;

        try {
            $expiresAtLabel = now()->addDays($expiresDays)->format('F d, Y');
            $html = '<div style="font-family: Arial, Helvetica, sans-serif; color: #222222; max-width: 560px; margin: 0; padding: 8px 0; font-size: 14px; line-height: 1.55;">'
                .'<div style="border-bottom: 2px solid #111111; padding-bottom: 10px; margin-bottom: 18px;">'
                .'<div style="font-size: 16px; font-weight: 700; color: #111111;">Boracay Lost and Found System</div>'
                .'<div style="font-size: 12px; color: #555555;">Staff Account Invitation</div>'
                .'</div>'
                .'<p style="margin: 0 0 14px 0;">Hello,</p>'
                .'<p style="margin: 0 0 16px 0;">An administrator has invited you to access the Boracay Lost and Found System with the following assignment:</p>'
                .'<table cellpadding="6" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 13px; border: 1px solid #d1d5db;">'
                .'<tr><td style="width: 36%; background: #f9fafb; border: 1px solid #d1d5db; font-weight: 700; color: #374151;">Station / Booth</td><td style="border: 1px solid #d1d5db;">'.e($deskName).'</td></tr>'
                .'<tr><td style="background: #f9fafb; border: 1px solid #d1d5db; font-weight: 700; color: #374151;">Assigned Role</td><td style="border: 1px solid #d1d5db;">'.e($role->name).'</td></tr>'
                .'<tr><td style="background: #f9fafb; border: 1px solid #d1d5db; font-weight: 700; color: #374151;">Authorized Email</td><td style="border: 1px solid #d1d5db;">'.e($validated['email']).'</td></tr>'
                .'<tr><td style="background: #f9fafb; border: 1px solid #d1d5db; font-weight: 700; color: #374151;">Valid Until</td><td style="border: 1px solid #d1d5db;">'.e($expiresAtLabel).' ('.$expiresDays.' days)</td></tr>'
                .'</table>'
                .'<p style="margin: 0 0 14px 0;">To activate your account, open the invitation link below and sign in with your Google account:</p>'
                .'<p style="margin: 0 0 16px 0;"><a href="'.e($inviteUrl).'" style="display: inline-block; background: #111827; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 3px;">Accept Invitation</a></p>'
                .'<p style="margin: 0 0 18px 0; font-size: 12px; color: #555555;">Direct link: <a href="'.e($inviteUrl).'" style="color: #1d4ed8; word-break: break-all;">'.e($inviteUrl).'</a></p>'
                .'<p style="margin: 0 0 6px 0;">Regards,<br><strong>Boracay Lost and Found System</strong></p>'
                .'<div style="border-top: 1px solid #e5e7eb; margin-top: 18px; padding-top: 10px; font-size: 11px; color: #777777;">This is an automated message. If you were not expecting this invitation, you can safely ignore this email.</div>'
                .'</div>';

            Mail::html($html, function ($message) use ($validated, $deskName): void {
                $message->to($validated['email'])
                    ->subject('['.config('app.name').'] '.__('Staff Invitation — :desk', ['desk' => $deskName]));
            });

            $emailSent = true;
        } catch (Throwable $exception) {
            Log::warning('Failed to send staff invitation email.', [
                'email' => $validated['email'],
                'error' => $exception->getMessage(),
            ]);
        }

        $statusMessage = $emailSent
            ? __('Invitation created and emailed to :email.', ['email' => $validated['email']])
            : __('Invitation created. Email could not be delivered — please copy and share the link below.');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'email_sent' => $emailSent,
                'message' => $statusMessage,
                'invite_url' => $inviteUrl,
            ]);
        }

        return redirect()->route('admin.invitations.index')
            ->with('status', $statusMessage)
            ->with('invite_url', $inviteUrl);
    }

    public function revoke(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        Gate::authorize('revoke', $invitation);

        $actor = $request->user();

        try {
            $this->invitationService->revokeInvitation($invitation, $actor, $request);
        } catch (InvitationException $exception) {
            return redirect()->route('admin.invitations.index')->withErrors(['invitation' => $exception->getMessage()]);
        }

        return redirect()->route('admin.invitations.index')->with('status', __('Invitation revoked.'));
    }
}
