<?php

namespace App\Providers;

use App\Models\Cat;
use App\Models\DmBanCat;
use App\Models\DmDonViCat;
use App\Models\DmDonViMay;
use App\Models\DmSize;
use App\Models\DonHang;
use App\Models\MatHang;
use App\Models\Mau;
use App\Models\NhapKho;
use App\Models\Permission;
use App\Models\PhanBoMay;
use App\Models\PhieuXuatKho;
use App\Models\Qc;
use App\Models\Role;
use App\Models\User;
use App\Observers\ActivityLogObserver;
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
        $this->registerActivityLogObservers();

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

    private function registerActivityLogObservers(): void
    {
        foreach ([
            MatHang::class,
            Mau::class,
            DmSize::class,
            DmBanCat::class,
            DmDonViCat::class,
            DmDonViMay::class,
            DonHang::class,
            Cat::class,
            PhanBoMay::class,
            Qc::class,
            NhapKho::class,
            PhieuXuatKho::class,
            User::class,
            Role::class,
            Permission::class,
        ] as $model) {
            $model::observe(ActivityLogObserver::class);
        }
    }
}
