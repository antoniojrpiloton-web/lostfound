@php
    $authUser = auth()->user();
    $initials = $authUser
        ? collect(explode(' ', trim($authUser->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('')
        : 'G';
    $supportedLocales = config('localization.supported', []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')
    <link rel="icon" type="image/png" href="{{ asset('images/boracay-lf-logo.png') }}">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    <a href="#main-content" class="skip-link">{{ __('Skip to main content') }}</a>

    <header class="sticky-top bg-white border-bottom" style="z-index: 1030;">
        <nav class="navbar navbar-expand-lg py-2" aria-label="{{ __('Primary Navigation') }}">
            <div class="container">
                <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none me-3 flex-shrink-0">
                    <img src="{{ asset('images/boracay-lf-logo.png') }}" alt="{{ __('Boracay Lost & Found') }}" width="38" height="38" class="flex-shrink-0" style="object-fit: contain;">
                    <div class="lh-sm">
                        <div class="fw-bold text-navy text-nowrap" style="font-size: 0.92rem;">{{ __('Boracay Lost & Found') }}</div>
                        <div class="text-secondary text-nowrap d-none d-xl-block" style="font-size: 0.67rem;">{{ __('Official Island Recovery Portal') }}</div>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-2 d-lg-none">
                    <button type="button" class="btn btn-sm btn-light border text-navy" data-open-smart-search="photo" aria-label="{{ __('Search by photo') }}">
                        <i class="bi bi-camera" aria-hidden="true"></i>
                    </button>
                    <button class="navbar-toggler border px-2 py-1" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbarCollapse" aria-controls="publicNavbarCollapse" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <i class="bi bi-list fs-5 text-navy" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="publicNavbarCollapse">
                    <ul class="navbar-nav me-auto align-items-lg-center gap-1 my-2 my-lg-0">
                        <li class="nav-item">
                            <a href="{{ route('home') }}" class="nav-link px-2 px-xl-3 py-1 rounded-2 small fw-medium text-nowrap {{ request()->routeIs('home') ? 'text-primary bg-light fw-semibold' : 'text-navy' }}">
                                {{ __('Home') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('public.items.index') }}" class="nav-link px-2 px-xl-3 py-1 rounded-2 small fw-medium text-nowrap {{ request()->routeIs('public.items.*') ? 'text-primary bg-light fw-semibold' : 'text-navy' }}">
                                {{ __('Browse') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.lost.create') }}" class="nav-link px-2 px-xl-3 py-1 rounded-2 small fw-medium text-nowrap {{ request()->routeIs('reports.lost.*') ? 'text-primary bg-light fw-semibold' : 'text-navy' }}">
                                {{ __('Report Lost') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.found.create') }}" class="nav-link px-2 px-xl-3 py-1 rounded-2 small fw-medium text-nowrap {{ request()->routeIs('reports.found.*') ? 'text-primary bg-light fw-semibold' : 'text-navy' }}">
                                {{ __('Report Found') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('public.help') }}" class="nav-link px-2 px-xl-3 py-1 rounded-2 small fw-medium text-nowrap {{ request()->routeIs('public.help') ? 'text-primary bg-light fw-semibold' : 'text-navy' }}">
                                {{ __('Help') }}
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-light border text-secondary d-none d-lg-inline-flex align-items-center gap-2 px-2 text-nowrap" data-open-smart-search="text" title="{{ __('Quick Search (Ctrl+K)') }}">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <span class="small d-none d-xl-inline">{{ __('Search') }}</span>
                            <kbd class="bg-white text-secondary border px-1" style="font-size: 0.65rem;">Ctrl K</kbd>
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border text-navy dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-translate me-1" aria-hidden="true"></i>{{ $supportedLocales[app()->getLocale()]['short'] ?? strtoupper(app()->getLocale()) }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                @foreach ($supportedLocales as $code => $meta)
                                    <li>
                                        <a class="dropdown-item small {{ app()->getLocale() === $code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}">
                                            {{ $meta['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @auth
                            @php
                                $publicUnreadCount = $authUser->unreadNotifications()->count();
                                $publicRecentNotifications = $authUser->unreadNotifications()->latest()->limit(5)->get();
                            @endphp
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-light border rounded-circle text-secondary d-inline-flex align-items-center justify-content-center position-relative" style="width: 34px; height: 34px;" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Notifications') }}">
                                    <i class="bi bi-bell" aria-hidden="true"></i>
                                    @if ($publicUnreadCount > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.62rem;">{{ $publicUnreadCount > 9 ? '9+' : $publicUnreadCount }}</span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm border" style="width: 300px;">
                                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                        <span class="small fw-semibold text-navy">{{ __('Notifications') }}</span>
                                        <a href="{{ route('notifications.index') }}" class="small text-decoration-none">{{ __('View all') }}</a>
                                    </div>
                                    @forelse ($publicRecentNotifications as $notif)
                                        <form method="POST" action="{{ route('notifications.read', $notif->id) }}" class="m-0 border-bottom">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="dropdown-item py-2 px-3 text-wrap small">
                                                <div class="fw-semibold text-navy">{{ $notif->data['title'] ?? __('Update') }}</div>
                                                <div class="text-secondary text-truncate">{{ $notif->data['body'] ?? '' }}</div>
                                            </button>
                                        </form>
                                    @empty
                                        <div class="p-3 text-center small text-secondary">
                                            {{ __('No unread notifications.') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            @if ($authUser->isInstitutionalUser())
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary fw-medium d-inline-flex align-items-center gap-1 text-nowrap">
                                    <i class="bi bi-speedometer2" aria-hidden="true"></i>{{ __('Admin') }}
                                </a>
                            @endif

                            <div class="dropdown">
                                <button type="button" class="btn btn-sm p-0 border-0 bg-transparent d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('User menu') }}">
                                    @if ($authUser->avatar_url)
                                        <img src="{{ $authUser->avatar_url }}" alt="" class="blf-avatar">
                                    @else
                                        <span class="blf-avatar" aria-hidden="true">{{ $initials }}</span>
                                    @endif
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                    <li>
                                        <div class="px-3 py-2">
                                            <div class="small fw-semibold text-navy">{{ $authUser->name }}</div>
                                            <div class="text-secondary" style="font-size: 0.75rem;">{{ $authUser->email }}</div>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('reports.mine') }}">
                                            <i class="bi bi-clock-history me-2" aria-hidden="true"></i>{{ __('My Reports') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item small" href="{{ route('claims.mine') }}">
                                            <i class="bi bi-patch-check me-2" aria-hidden="true"></i>{{ __('My Claims') }}
                                        </a>
                                    </li>
                                    @if ($authUser->isInstitutionalUser())
                                        <li>
                                            <a class="dropdown-item small" href="{{ route('admin.dashboard') }}">
                                                <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>{{ __('Admin Console') }}
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item small text-danger">
                                                <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>{{ __('Sign Out') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sm btn-primary fw-medium px-3">
                                <i class="bi bi-google me-1" aria-hidden="true"></i>{{ __('Sign In with Google') }}
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="main-content" class="flex-grow-1">
        @hasSection('full_bleed')
            @yield('content')
        @else
            <div class="container py-4">
                @yield('content')
            </div>
        @endif
    </main>

    <footer class="bg-white border-top mt-5">
        <div class="container py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small text-secondary">
                <div>
                    <strong class="text-navy">{{ config('app.name') }}</strong>
                    <span class="mx-2">·</span>
                    <span>{{ __('Boracay Island, Malay, Aklan') }}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('public.items.index') }}" class="text-decoration-none text-secondary">{{ __('Browse Listings') }}</a>
                    <a href="{{ route('public.help') }}" class="text-decoration-none text-secondary">{{ __('Help & Privacy') }}</a>
                    <span>&copy; {{ now()->year }}</span>
                </div>
            </div>
        </div>
    </footer>

    <x-toast-stack />
    <x-confirm-modal />
    <x-smart-search-modal />
    @stack('scripts')
</body>
</html>
