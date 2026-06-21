<?php

if (! function_exists('formatPhanBoNumber')) {
    function formatPhanBoNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $number = (float) $value;
        $formatted = number_format($number, 4, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}

if (! function_exists('hasPermission')) {
    function hasPermission(string $permissionCode): bool
    {
        $user = request()->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermission($permissionCode);
    }
}

if (! function_exists('menuPermissionCode')) {
    function menuPermissionCode(?string $routeName): ?string
    {
        return match ($routeName) {
            'dashboard-analytics' => 'DASHBOARD_VIEW',
            'mat-hang.index', 'mat-hang.create', 'mat-hang.edit', 'mat-hang.destroy' => 'DANH_MUC_VIEW',
            'mau.index', 'mau.create', 'mau.edit', 'mau.destroy' => 'DANH_MUC_VIEW',
            'size.index', 'size.create', 'size.edit', 'size.destroy' => 'DANH_MUC_VIEW',
            'don-vi-cat.index', 'don-vi-cat.create', 'don-vi-cat.edit', 'don-vi-cat.destroy' => 'DANH_MUC_VIEW',
            'don-vi-may.index', 'don-vi-may.create', 'don-vi-may.edit', 'don-vi-may.destroy' => 'DANH_MUC_VIEW',
            'loai-don-vi-may.index', 'loai-don-vi-may.create', 'loai-don-vi-may.edit', 'loai-don-vi-may.destroy' => 'DANH_MUC_VIEW',
            'don-hangs', 'don-hangs.index', 'don-hangs.create', 'don-hangs.store', 'don-hangs.show', 'don-hangs.edit', 'don-hangs.update', 'don-hangs.destroy' => 'DON_HANG_VIEW',
            'cat.index', 'cat.create', 'cat.edit', 'cat.destroy' => 'CAT_VIEW',
            'phan-bo-may.index', 'phan-bo-may.create', 'phan-bo-may.edit', 'phan-bo-may.destroy' => 'PHAN_BO_MAY_VIEW',
            'qc.index', 'qc.create', 'qc.edit', 'qc.destroy' => 'QC_VIEW',
            'nhap-kho.index', 'nhap-kho.create', 'nhap-kho.edit', 'nhap-kho.destroy' => 'NHAP_KHO_VIEW',
            'xuat-kho.index', 'xuat-kho.create', 'xuat-kho.edit', 'xuat-kho.destroy' => 'XUAT_KHO_VIEW',
            'ton-kho.index' => 'TON_KHO_VIEW',
            'bao-cao.tong-hop-don-hang' => 'BAO_CAO_TONG_HOP_DON_HANG_VIEW',
            'role.index', 'role.create', 'role.edit', 'role.destroy' => 'ROLE_VIEW',
            'permission.index', 'permission.create', 'permission.edit', 'permission.destroy' => 'PERMISSION_VIEW',
            'role-permission.index', 'role-permission.edit' => 'ROLE_PERMISSION_VIEW',
            'user.index', 'user.create', 'user.edit', 'user.destroy' => 'USER_VIEW',
            'profile.index' => 'PROFILE_VIEW',
            'profile.update' => 'PROFILE_EDIT',
            'profile.change-password', 'profile.update-password' => 'CHANGE_PASSWORD',
            default => null,
        };
    }
}

if (! function_exists('menuItemVisible')) {
    function menuItemVisible(object $menu): bool
    {
        $currentRouteName = request()->route()?->getName();
        $menuSlug = is_array($menu->slug ?? null) ? null : ($menu->slug ?? null);

        if (isset($menu->submenu) && is_iterable($menu->submenu)) {
            foreach ($menu->submenu as $submenu) {
                if (menuItemVisible($submenu)) {
                    return true;
                }
            }

            if ($menuSlug === null) {
                return false;
            }
        }

        $permissionCode = menuPermissionCode($menuSlug);

        if ($permissionCode === null) {
            return true;
        }

        return hasPermission($permissionCode) || ($currentRouteName !== null && $currentRouteName === $menuSlug && request()->user()?->isAdmin());
    }
}
