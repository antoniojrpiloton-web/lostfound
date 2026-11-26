<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\GoogleAuthenticationException;
use App\Exceptions\InvitationException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\GoogleAuthService;
use App\Services\Organization\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            return $user instanceof User && $user->isInstitutionalUser()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('home');
        }

        return view('auth.login');
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'google' => __('Google Sign-In is not configured yet. Please contact the system administrator.'),
            ]);
        }

        if ($request->filled('invite')) {
            $request->session()->put('oauth_invite_token', (string) $request->string('invite'));
        }

        return Socialite::driver('google')
            ->setScopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(
        Request $request,
        GoogleAuthService $googleAuthService,
        InvitationService $invitationService,
    ): RedirectResponse {
        if ($request->filled('error')) {
            $message = $request->string('error')->toString() === 'access_denied'
                ? __('Google sign-in was cancelled.')
                : __('Google authentication could not be completed. Please try again.');

            return redirect()->route('login')->withErrors(['google' => $message]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors([
                'google' => __('Your sign-in session expired or was invalid. Please try again.'),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Google OAuth callback failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'google' => __('Unable to complete Google sign-in. Please try again.'),
            ]);
        }

        try {
            $user = $googleAuthService->resolveUser($googleUser, $request);
        } catch (GoogleAuthenticationException $exception) {
            return redirect()->route('login')->withErrors([
                'google' => $exception->getMessage(),
            ]);
        }

        $inviteToken = $request->session()->pull('oauth_invite_token');

        Auth::login($user, true);
        $request->session()->regenerate();

        if (is_string($inviteToken) && $inviteToken !== '') {
            try {
                $invitationService->acceptInvitation($inviteToken, $user, $request);

                return redirect()->route('admin.dashboard')->with('status', __('Signed in with Google and institutional access activated.'));
            } catch (InvitationException $exception) {
                return redirect()->route('home')
                    ->with('status', __('Signed in with Google.'))
                    ->withErrors(['invitation' => $exception->getMessage()]);
            }
        }

        $defaultRedirect = $user->isInstitutionalUser() ? route('admin.dashboard') : route('home');

        return redirect()->intended($defaultRedirect)->with('status', __('Signed in with Google.'));
    }

    public function logout(Request $request, GoogleAuthService $googleAuthService): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $googleAuthService->recordLogout($user, $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('You have been signed out.'));
    }
}
