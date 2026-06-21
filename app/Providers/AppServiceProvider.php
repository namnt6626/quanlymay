<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        $this->useBuildAssetsOutsideLocalhost();

        Gate::before(function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('permission', function (User $user, string $permissionCode): bool {
            return $user->hasPermission($permissionCode);
        });
    }

    private function useBuildAssetsOutsideLocalhost(): void
    {
        if (! app()->runningInConsole() && ! in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true)) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }
    }
}
