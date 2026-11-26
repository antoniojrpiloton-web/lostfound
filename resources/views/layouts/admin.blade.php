@php
    $authUser = auth()->user();
    $membership = $authUser?->activeInstitutionalMembership();
    $initials = collect(explode(' ', trim($authUser?->name ?? 'U')))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google" content="notranslate">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/boracay-lf-logo.png') }}">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="blf-admin-body">
    <a href="#main-content" class="skip-link">{{ __('Skip to main content') }}</a>

    <div class="blf-admin-shell" data-admin-shell>
        <div class="blf-sidebar-backdrop" data-sidebar-backdrop></div>

        <aside class="blf-sidebar" aria-label="{{ __('Institutional Navigation') }}">
            <a href="{{ route('admin.dashboard') }}" class="blf-sidebar-brand">
                <img src="{{ asset('images/boracay-lf-logo.png') }}" alt="{{ __('Boracay L&F') }}" width="36" height="36" class="flex-shrink-0" style="object-fit: contain;">
                <div class="blf-sidebar-text lh-sm">
                    <div class="fw-bold small text-navy">{{ __('Boracay L&F') }}</div>
                    <div class="text-secondary" style="font-size: 0.7rem; font-family: ui-monospace, monospace;">{{ __('island · v1.0') }}</div>
                </div>
            </a>

            <nav class="blf-sidebar-nav">
                @php
                    $isUsersGroupActive = request()->routeIs('admin.organizations.*', 'admin.memberships.*', 'admin.invitations.*');
                    $isMasterDataActive = request()->routeIs('admin.categories.*', 'admin.locations.*');
                    $isSystemGroupActive = request()->routeIs('admin.settings.*', 'admin.audit_logs.*', 'admin.system_health.*');
                @endphp

                <div class="blf-sidebar-section">{{ __('Operations') }}</div>
                <a href="{{ route('admin.dashboard') }}" class="blf-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                    <span class="blf-sidebar-text">{{ __('Dashboard') }}</span>
                </a>
                @if ($authUser?->hasPermission('report.view_org'))
                    <a href="{{ route('admin.reports.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.reports.*', 'admin.found.*', 'admin.images.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                        <span class="blf-sidebar-text">{{ __('Reports') }}</span>
                    </a>
                @endif
                @if ($authUser?->hasPermission('match.view'))
                    <a href="{{ route('admin.matches.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}">
                        <i class="bi bi-cpu" aria-hidden="true"></i>
                        <span class="blf-sidebar-text">{{ __('Item Matches') }}</span>
                        <i class="bi bi-lock-fill text-secondary opacity-50 ms-auto blf-sidebar-text" style="font-size: 0.72rem;" aria-hidden="true"></i>
                    </a>
                @endif
                @if ($authUser?->hasPermission('claim.view_org'))
                    <a href="{{ route('admin.claims.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.claims.*') ? 'active' : '' }}">
                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                        <span class="blf-sidebar-text">{{ __('Claims') }}</span>
                    </a>
                @endif
                @if ($authUser?->hasPermission('recovery.record'))
                    <a href="{{ route('admin.recoveries.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.recoveries.*') ? 'active' : '' }}">
                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                        <span class="blf-sidebar-text">{{ __('Recoveries') }}</span>
                    </a>
                @endif
                @if ($authUser?->hasPermission('disposal.view'))
                    <a href="{{ route('admin.disposals.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.disposals.*') ? 'active' : '' }}">
                        <i class="bi bi-archive" aria-hidden="true"></i>
                        <span class="blf-sidebar-text">{{ __('Disposals') }}</span>
                        <i class="bi bi-lock-fill text-secondary opacity-50 ms-auto blf-sidebar-text" style="font-size: 0.72rem;" aria-hidden="true"></i>
                    </a>
                @endif
                @if ($authUser?->hasPermission('analytics.view'))
                    <a href="{{ route('admin.analytics.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span class="blf-sidebar-text">{{ __('Analytics') }}</span>
                        <i class="bi bi-lock-fill text-secondary opacity-50 ms-auto blf-sidebar-text" style="font-size: 0.72rem;" aria-hidden="true"></i>
                    </a>
                @endif

                <div class="blf-sidebar-section">{{ __('Manage') }}</div>

                @if ($authUser?->can('viewAny', App\Models\Organization::class)
                    || $authUser?->can('viewAny', App\Models\OrganizationMembership::class)
                    || $authUser?->can('viewAny', App\Models\OrganizationInvitation::class))
                    <a
                        href="#adminUsersMenu"
                        data-bs-toggle="collapse"
                        aria-expanded="{{ $isUsersGroupActive ? 'true' : 'false' }}"
                        aria-controls="adminUsersMenu"
                        class="blf-sidebar-link justify-content-between {{ $isUsersGroupActive ? 'active' : '' }}"
                    >
                        <span class="d-flex align-items-center gap-2 min-w-0">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span class="blf-sidebar-text">{{ __('Users & Stations') }}</span>
                        </span>
                        <i class="bi bi-chevron-down small blf-sidebar-text" aria-hidden="true"></i>
                    </a>
                    <div class="collapse {{ $isUsersGroupActive ? 'show' : '' }} ps-3" id="adminUsersMenu">
                        @can('viewAny', App\Models\Organization::class)
                            <a href="{{ route('admin.organizations.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.organizations.*') ? 'active' : '' }}">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Stations & Booths') }}</span>
                            </a>
                        @endcan
                        @can('viewAny', App\Models\OrganizationMembership::class)
                            <a href="{{ route('admin.memberships.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.memberships.*') ? 'active' : '' }}">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Staff Accounts') }}</span>
                            </a>
                        @endcan
                        @can('viewAny', App\Models\OrganizationInvitation::class)
                            <a href="{{ route('admin.invitations.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.invitations.*') ? 'active' : '' }}">
                                <i class="bi bi-envelope-plus" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Invitations') }}</span>
                            </a>
                        @endcan
                    </div>
                @endif

                @if ($authUser?->can('viewAny', App\Models\Category::class)
                    || $authUser?->can('viewAny', App\Models\Location::class))
                    <a
                        href="#adminMasterDataMenu"
                        data-bs-toggle="collapse"
                        aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}"
                        aria-controls="adminMasterDataMenu"
                        class="blf-sidebar-link justify-content-between {{ $isMasterDataActive ? 'active' : '' }}"
                    >
                        <span class="d-flex align-items-center gap-2 min-w-0">
                            <i class="bi bi-database" aria-hidden="true"></i>
                            <span class="blf-sidebar-text">{{ __('Master Data') }}</span>
                        </span>
                        <i class="bi bi-chevron-down small blf-sidebar-text" aria-hidden="true"></i>
                    </a>
                    <div class="collapse {{ $isMasterDataActive ? 'show' : '' }} ps-3" id="adminMasterDataMenu">
                        @can('viewAny', App\Models\Category::class)
                            <a href="{{ route('admin.categories.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                <i class="bi bi-tags" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Categories') }}</span>
                            </a>
                        @endcan
                        @can('viewAny', App\Models\Location::class)
                            <a href="{{ route('admin.locations.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
                                <i class="bi bi-pin-map" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Locations') }}</span>
                            </a>
                        @endcan
                    </div>
                @endif

                @if ($authUser?->can('viewAny', App\Models\SystemSetting::class)
                    || $authUser?->hasPermission('analytics.view'))
                    <a
                        href="#adminSystemMenu"
                        data-bs-toggle="collapse"
                        aria-expanded="{{ $isSystemGroupActive ? 'true' : 'false' }}"
                        aria-controls="adminSystemMenu"
                        class="blf-sidebar-link justify-content-between {{ $isSystemGroupActive ? 'active' : '' }}"
                    >
                        <span class="d-flex align-items-center gap-2 min-w-0">
                            <i class="bi bi-gear" aria-hidden="true"></i>
                            <span class="blf-sidebar-text">{{ __('System') }}</span>
                        </span>
                        <i class="bi bi-chevron-down small blf-sidebar-text" aria-hidden="true"></i>
                    </a>
                    <div class="collapse {{ $isSystemGroupActive ? 'show' : '' }} ps-3" id="adminSystemMenu">
                        @can('viewAny', App\Models\SystemSetting::class)
                            <a href="{{ route('admin.settings.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                <i class="bi bi-sliders" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Settings') }}</span>
                            </a>
                        @endcan
                        @if ($authUser?->hasPermission('analytics.view'))
                            <a href="{{ route('admin.audit_logs.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.audit_logs.*') ? 'active' : '' }}">
                                <i class="bi bi-journal-text" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Audit Logs') }}</span>
                                <i class="bi bi-lock-fill text-secondary opacity-50 ms-auto blf-sidebar-text" style="font-size: 0.72rem;" aria-hidden="true"></i>
                            </a>
                        @endif
                        @can('viewAny', App\Models\SystemSetting::class)
                            <a href="{{ route('admin.system_health.index') }}" class="blf-sidebar-link {{ request()->routeIs('admin.system_health.*') ? 'active' : '' }}">
                                <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                                <span class="blf-sidebar-text">{{ __('Health & Backups') }}</span>
                                <i class="bi bi-lock-fill text-secondary opacity-50 ms-auto blf-sidebar-text" style="font-size: 0.72rem;" aria-hidden="true"></i>
                            </a>
                        @endcan
                    </div>
                @endif

                <a href="{{ route('home') }}" class="blf-sidebar-link mt-2">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    <span class="blf-sidebar-text">{{ __('Public Portal') }}</span>
                </a>
            </nav>

            <div class="blf-sidebar-footer">
                @if ($authUser?->avatar_url)
                    <img src="{{ $authUser->avatar_url }}" alt="" class="blf-avatar">
                @else
                    <span class="blf-avatar" aria-hidden="true">{{ $initials }}</span>
                @endif
                <div class="blf-sidebar-text text-truncate lh-sm">
                    <div class="small fw-semibold text-navy text-truncate">{{ $authUser?->name }}</div>
                    <div class="text-secondary text-truncate" style="font-size: 0.72rem;">{{ $membership?->role?->name }}</div>
                </div>
            </div>
        </aside>

        <div class="blf-admin-main">
            <header class="blf-topbar">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <button type="button" class="btn btn-sm btn-light border-0 text-secondary" data-sidebar-toggle aria-expanded="true" aria-label="{{ __('Toggle sidebar') }}">
                        <i class="bi bi-list fs-5" aria-hidden="true"></i>
                    </button>

                    <div class="d-none d-sm-block text-truncate">
                        @hasSection('breadcrumbs')
                            @yield('breadcrumbs')
                        @else
                            <span class="small fw-medium text-navy">@yield('title', __('Dashboard'))</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="blf-topbar-search d-none d-md-block">
                        <i class="bi bi-search blf-search-icon" aria-hidden="true"></i>
                        <input type="search" readonly role="button" class="form-control" placeholder="{{ __('Smart search...') }}" data-global-search data-open-smart-search="text" aria-label="{{ __('Global search') }}">
                        <kbd>Ctrl K</kbd>
                    </div>
                    <button type="button" class="btn btn-sm btn-light bg-white border text-primary d-inline-flex align-items-center gap-1" data-open-smart-search="photo" title="{{ __('Smart Photo Search') }}">
                        <i class="bi bi-camera" aria-hidden="true"></i>
                    </button>

                    <div class="dropdown">
                        @php $supportedLocales = config('localization.supported', []); @endphp
                        <button class="btn btn-sm btn-light bg-white border text-navy dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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

                    @php
                        $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
                        $recentNotifications = $authUser ? $authUser->unreadNotifications()->latest()->limit(5)->get() : collect();
                    @endphp
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-light bg-white border rounded-circle text-secondary d-inline-flex align-items-center justify-content-center position-relative" style="width: 34px; height: 34px;" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Notifications') }}">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            @if ($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.62rem;">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm border" style="width: 300px;">
                            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                                <span class="small fw-semibold text-navy">{{ __('Notifications') }}</span>
                                <a href="{{ route('notifications.index') }}" class="small text-decoration-none text-ocean">{{ __('View all') }}</a>
                            </div>
                            @forelse ($recentNotifications as $notif)
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

                    <div class="dropdown">
                        <button type="button" class="btn btn-sm p-0 border-0 bg-transparent d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('User menu') }}">
                            @if ($authUser?->avatar_url)
                                <img src="{{ $authUser->avatar_url }}" alt="" class="blf-avatar">
                            @else
                                <span class="blf-avatar" aria-hidden="true">{{ $initials }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                            <li>
                                <div class="px-3 py-2">
                                    <div class="small fw-semibold text-navy">{{ $authUser?->name }}</div>
                                    <div class="text-secondary" style="font-size: 0.75rem;">{{ $membership?->organization?->name ?? __('Platform-Wide') }}</div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item small" href="{{ route('home') }}">
                                    <i class="bi bi-globe2 me-2" aria-hidden="true"></i>{{ __('Public Portal') }}
                                </a>
                            </li>
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
                </div>
            </header>

            <main id="main-content" class="blf-admin-content">
                @hasSection('hero-header')
                    @yield('hero-header')
                @else
                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                        <div>
                            <div class="blf-page-eyebrow">@yield('eyebrow', __('Workspace'))</div>
                            <h1 class="h4 text-navy fw-bold mb-0">@yield('title', __('Dashboard'))</h1>
                        </div>
                        @hasSection('actions')
                            <div class="d-flex align-items-center gap-2">
                                @yield('actions')
                            </div>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <x-toast-stack />
    <x-confirm-modal />
    <x-smart-search-modal />
    @stack('scripts')
</body>
</html>
