<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstitutionalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->status !== UserStatus::Active || ! $user->isInstitutionalUser()) {
            abort(403, __('Institutional authorization is required to access this area.'));
        }

        return $next($request);
    }
}
