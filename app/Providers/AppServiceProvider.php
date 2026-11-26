<?php

namespace App\Providers;

use App\Contracts\Matching\ImageSimilarityContract;
use App\Enums\UserStatus;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ItemMatch;
use App\Models\ItemReport;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\BarangayPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ClaimPolicy;
use App\Policies\ItemMatchPolicy;
use App\Policies\ItemReportPolicy;
use App\Policies\LocationPolicy;
use App\Policies\OrganizationInvitationPolicy;
use App\Policies\OrganizationMembershipPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\SystemSettingPolicy;
use App\Services\Matching\ClipImageSimilarityProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ImageSimilarityContract::class, ClipImageSimilarityProvider::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(OrganizationMembership::class, OrganizationMembershipPolicy::class);
        Gate::policy(OrganizationInvitation::class, OrganizationInvitationPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Barangay::class, BarangayPolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
        Gate::policy(ItemReport::class, ItemReportPolicy::class);
        Gate::policy(ItemMatch::class, ItemMatchPolicy::class);
        Gate::policy(Claim::class, ClaimPolicy::class);

        Gate::define('access-admin', fn (User $user): bool => $user->status === UserStatus::Active && $user->isInstitutionalUser());
        Gate::define('permission', fn (User $user, string $permission): bool => $user->hasPermission($permission));
    }
}
