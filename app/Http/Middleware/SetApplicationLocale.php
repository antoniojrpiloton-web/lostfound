<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    public function handle(Request $request, Closure $next): Response
    {

        $supported = config('localization.supported', ['en' => ['label' => 'English', 'short' => 'EN']]);
        $supportedCodes = array_keys($supported);
        $defaultLocale = (string) config('localization.default', 'en');

        $requested = strtolower(trim((string) $request->query('lang', '')));
        $locale = null;

        if ($requested !== '' && in_array($requested, $supportedCodes, true)) {
            $locale = $requested;
            $request->session()->put('locale', $locale);

            $user = $request->user();
            if ($user instanceof User && $user->locale !== $locale) {
                $user->forceFill(['locale' => $locale])->save();
            }
        } elseif ($request->session()->has('locale')) {
            $sessionLocale = (string) $request->session()->get('locale');
            if (in_array($sessionLocale, $supportedCodes, true)) {
                $locale = $sessionLocale;
            }
        } elseif ($request->user() instanceof User && in_array((string) $request->user()->locale, $supportedCodes, true)) {
            $locale = (string) $request->user()->locale;
        } else {
            $preferred = $this->detectBrowserLocale($request, $supportedCodes);
            $locale = $preferred ?? $defaultLocale;
        }

        App::setLocale($locale);

        return $next($request);
    }

    protected function detectBrowserLocale(Request $request, array $supportedCodes): ?string
    {
        $header = (string) $request->header('Accept-Language', '');
        if ($header === '') {
            return null;
        }

        foreach (explode(',', $header) as $segment) {
            $tag = strtolower(trim(explode(';', $segment)[0]));
            if ($tag === '') {
                continue;
            }

            if (in_array($tag, $supportedCodes, true)) {
                return $tag;
            }

            $primary = explode('-', $tag)[0];
            if ($primary === 'tl' && in_array('fil', $supportedCodes, true)) {
                return 'fil';
            }

            if (in_array($primary, $supportedCodes, true)) {
                return $primary;
            }
        }

        return null;
    }
}
