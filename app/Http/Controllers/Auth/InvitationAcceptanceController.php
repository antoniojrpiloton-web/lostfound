<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Organization\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationAcceptanceController extends Controller
{
    public function accept(Request $request, string $token, InvitationService $invitationService): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('auth.google.redirect', ['invite' => $token]);
        }

        try {
            $invitationService->acceptInvitation($token, $user, $request);
        } catch (InvitationException $exception) {
            return redirect()->route('home')->withErrors(['invitation' => $exception->getMessage()]);
        }

        return redirect()->route('admin.dashboard')->with('status', __('Your institutional access has been activated.'));
    }
}
