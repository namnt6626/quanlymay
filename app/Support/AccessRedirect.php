<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AccessRedirect
{
    /**
     * @return array<int, array{route: string, permission: string|null}>
     */
    public function routePriority(): array
    {
        return [
            ['route' => 'dashboard-analytics', 'permission' => 'DASHBOARD_VIEW'],
            ['route' => 'don-hangs.index', 'permission' => 'DON_HANG_VIEW'],
            ['route' => 'cat.index', 'permission' => 'CAT_VIEW'],
            ['route' => 'phan-bo-may.index', 'permission' => 'PHAN_BO_MAY_VIEW'],
            ['route' => 'qc.index', 'permission' => 'QC_VIEW'],
            ['route' => 'nhap-kho.index', 'permission' => 'NHAP_KHO_VIEW'],
            ['route' => 'xuat-kho.index', 'permission' => 'XUAT_KHO_VIEW'],
            ['route' => 'ton-kho.index', 'permission' => 'TON_KHO_VIEW'],
            ['route' => 'bao-cao.tong-hop-don-hang', 'permission' => 'BAO_CAO_TONG_HOP_DON_HANG_VIEW'],
            ['route' => 'mat-hang.index', 'permission' => 'DANH_MUC_VIEW'],
            ['route' => 'mau.index', 'permission' => 'DANH_MUC_VIEW'],
            ['route' => 'size.index', 'permission' => 'DANH_MUC_VIEW'],
            ['route' => 'don-vi-cat.index', 'permission' => 'DANH_MUC_VIEW'],
            ['route' => 'don-vi-may.index', 'permission' => 'DANH_MUC_VIEW'],
            ['route' => 'role.index', 'permission' => 'ROLE_VIEW'],
            ['route' => 'permission.index', 'permission' => 'PERMISSION_VIEW'],
            ['route' => 'role-permission.index', 'permission' => 'ROLE_PERMISSION_VIEW'],
            ['route' => 'user.index', 'permission' => 'USER_VIEW'],
            ['route' => 'activity-logs.index', 'permission' => 'ACTIVITY_LOG_VIEW'],
            ['route' => 'profile.index', 'permission' => 'PROFILE_VIEW'],
        ];
    }

    public function firstAccessibleRoute(User $user): ?string
    {
        if ($user->isAdmin() && Route::has('dashboard-analytics')) {
            return 'dashboard-analytics';
        }

        foreach ($this->routePriority() as $item) {
            if (! Route::has($item['route'])) {
                continue;
            }

            if ($item['permission'] === null || $user->hasPermission($item['permission'])) {
                return $item['route'];
            }
        }

        return null;
    }
}
