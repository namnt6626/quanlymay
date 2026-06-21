<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['ma_quyen' => 'DANH_MUC_VIEW', 'ten_quyen' => 'Xem danh mục', 'module' => 'Danh mục', 'action' => 'VIEW'],
            ['ma_quyen' => 'DANH_MUC_CREATE', 'ten_quyen' => 'Thêm danh mục', 'module' => 'Danh mục', 'action' => 'CREATE'],
            ['ma_quyen' => 'DANH_MUC_EDIT', 'ten_quyen' => 'Sửa danh mục', 'module' => 'Danh mục', 'action' => 'EDIT'],
            ['ma_quyen' => 'DANH_MUC_DELETE', 'ten_quyen' => 'Xóa danh mục', 'module' => 'Danh mục', 'action' => 'DELETE'],

            ['ma_quyen' => 'CAT_VIEW', 'ten_quyen' => 'Xem Cắt', 'module' => 'Cắt', 'action' => 'VIEW'],
            ['ma_quyen' => 'CAT_CREATE', 'ten_quyen' => 'Thêm Cắt', 'module' => 'Cắt', 'action' => 'CREATE'],
            ['ma_quyen' => 'CAT_EDIT', 'ten_quyen' => 'Sửa Cắt', 'module' => 'Cắt', 'action' => 'EDIT'],
            ['ma_quyen' => 'CAT_DELETE', 'ten_quyen' => 'Xóa Cắt', 'module' => 'Cắt', 'action' => 'DELETE'],

            ['ma_quyen' => 'PHAN_BO_MAY_VIEW', 'ten_quyen' => 'Xem Phân bổ may', 'module' => 'Phân bổ may', 'action' => 'VIEW'],
            ['ma_quyen' => 'PHAN_BO_MAY_CREATE', 'ten_quyen' => 'Thêm Phân bổ may', 'module' => 'Phân bổ may', 'action' => 'CREATE'],
            ['ma_quyen' => 'PHAN_BO_MAY_EDIT', 'ten_quyen' => 'Sửa Phân bổ may', 'module' => 'Phân bổ may', 'action' => 'EDIT'],
            ['ma_quyen' => 'PHAN_BO_MAY_DELETE', 'ten_quyen' => 'Xóa Phân bổ may', 'module' => 'Phân bổ may', 'action' => 'DELETE'],

            ['ma_quyen' => 'QC_VIEW', 'ten_quyen' => 'Xem QC', 'module' => 'QC', 'action' => 'VIEW'],
            ['ma_quyen' => 'QC_CREATE', 'ten_quyen' => 'Thêm QC', 'module' => 'QC', 'action' => 'CREATE'],
            ['ma_quyen' => 'QC_EDIT', 'ten_quyen' => 'Sửa QC', 'module' => 'QC', 'action' => 'EDIT'],
            ['ma_quyen' => 'QC_DELETE', 'ten_quyen' => 'Xóa QC', 'module' => 'QC', 'action' => 'DELETE'],

            ['ma_quyen' => 'NHAP_KHO_VIEW', 'ten_quyen' => 'Xem Nhập kho', 'module' => 'Nhập kho', 'action' => 'VIEW'],
            ['ma_quyen' => 'NHAP_KHO_CREATE', 'ten_quyen' => 'Thêm Nhập kho', 'module' => 'Nhập kho', 'action' => 'CREATE'],
            ['ma_quyen' => 'NHAP_KHO_EDIT', 'ten_quyen' => 'Sửa Nhập kho', 'module' => 'Nhập kho', 'action' => 'EDIT'],
            ['ma_quyen' => 'NHAP_KHO_DELETE', 'ten_quyen' => 'Xóa Nhập kho', 'module' => 'Nhập kho', 'action' => 'DELETE'],

            ['ma_quyen' => 'XUAT_KHO_VIEW', 'ten_quyen' => 'Xem Xuất kho', 'module' => 'Xuất kho', 'action' => 'VIEW'],
            ['ma_quyen' => 'XUAT_KHO_CREATE', 'ten_quyen' => 'Thêm Xuất kho', 'module' => 'Xuất kho', 'action' => 'CREATE'],
            ['ma_quyen' => 'XUAT_KHO_EDIT', 'ten_quyen' => 'Sửa Xuất kho', 'module' => 'Xuất kho', 'action' => 'EDIT'],
            ['ma_quyen' => 'XUAT_KHO_DELETE', 'ten_quyen' => 'Xóa Xuất kho', 'module' => 'Xuất kho', 'action' => 'DELETE'],

            ['ma_quyen' => 'DON_HANG_VIEW', 'ten_quyen' => 'Xem Đơn hàng', 'module' => 'Đơn hàng', 'action' => 'VIEW'],
            ['ma_quyen' => 'DON_HANG_CREATE', 'ten_quyen' => 'Thêm Đơn hàng', 'module' => 'Đơn hàng', 'action' => 'CREATE'],
            ['ma_quyen' => 'DON_HANG_UPDATE', 'ten_quyen' => 'Sửa Đơn hàng', 'module' => 'Đơn hàng', 'action' => 'UPDATE'],
            ['ma_quyen' => 'DON_HANG_DELETE', 'ten_quyen' => 'Xóa Đơn hàng', 'module' => 'Đơn hàng', 'action' => 'DELETE'],

            ['ma_quyen' => 'DASHBOARD_VIEW', 'ten_quyen' => 'Xem Dashboard', 'module' => 'Dashboard', 'action' => 'VIEW'],

            ['ma_quyen' => 'TON_KHO_VIEW', 'ten_quyen' => 'Xem Tồn kho', 'module' => 'Tồn kho', 'action' => 'VIEW'],

            ['ma_quyen' => 'BAO_CAO_VIEW', 'ten_quyen' => 'Xem Báo cáo', 'module' => 'Báo cáo', 'action' => 'VIEW'],
            ['ma_quyen' => 'BAO_CAO_TONG_HOP_DON_HANG_VIEW', 'ten_quyen' => 'Xem Báo cáo tổng hợp đơn hàng', 'module' => 'Báo cáo', 'action' => 'VIEW'],

            ['ma_quyen' => 'ROLE_VIEW', 'ten_quyen' => 'Xem Vai trò', 'module' => 'Tài khoản', 'action' => 'VIEW'],
            ['ma_quyen' => 'ROLE_CREATE', 'ten_quyen' => 'Thêm Vai trò', 'module' => 'Tài khoản', 'action' => 'CREATE'],
            ['ma_quyen' => 'ROLE_EDIT', 'ten_quyen' => 'Sửa Vai trò', 'module' => 'Tài khoản', 'action' => 'EDIT'],
            ['ma_quyen' => 'ROLE_DELETE', 'ten_quyen' => 'Xóa Vai trò', 'module' => 'Tài khoản', 'action' => 'DELETE'],

            ['ma_quyen' => 'PERMISSION_VIEW', 'ten_quyen' => 'Xem Quyền', 'module' => 'Tài khoản', 'action' => 'VIEW'],
            ['ma_quyen' => 'PERMISSION_CREATE', 'ten_quyen' => 'Thêm Quyền', 'module' => 'Tài khoản', 'action' => 'CREATE'],
            ['ma_quyen' => 'PERMISSION_EDIT', 'ten_quyen' => 'Sửa Quyền', 'module' => 'Tài khoản', 'action' => 'EDIT'],
            ['ma_quyen' => 'PERMISSION_DELETE', 'ten_quyen' => 'Xóa Quyền', 'module' => 'Tài khoản', 'action' => 'DELETE'],

            ['ma_quyen' => 'ROLE_PERMISSION_VIEW', 'ten_quyen' => 'Xem Phân quyền', 'module' => 'Tài khoản', 'action' => 'VIEW'],
            ['ma_quyen' => 'ROLE_PERMISSION_EDIT', 'ten_quyen' => 'Sửa Phân quyền', 'module' => 'Tài khoản', 'action' => 'EDIT'],

            ['ma_quyen' => 'USER_VIEW', 'ten_quyen' => 'Xem Người dùng', 'module' => 'Tài khoản', 'action' => 'VIEW'],
            ['ma_quyen' => 'USER_CREATE', 'ten_quyen' => 'Thêm Người dùng', 'module' => 'Tài khoản', 'action' => 'CREATE'],
            ['ma_quyen' => 'USER_EDIT', 'ten_quyen' => 'Sửa Người dùng', 'module' => 'Tài khoản', 'action' => 'EDIT'],
            ['ma_quyen' => 'USER_DELETE', 'ten_quyen' => 'Xóa Người dùng', 'module' => 'Tài khoản', 'action' => 'DELETE'],

            ['ma_quyen' => 'ACTIVITY_LOG_VIEW', 'ten_quyen' => 'Xem Nhật ký thao tác', 'module' => 'Tài khoản', 'action' => 'VIEW'],

            ['ma_quyen' => 'PROFILE_VIEW', 'ten_quyen' => 'Xem Hồ sơ cá nhân', 'module' => 'Tài khoản', 'action' => 'VIEW'],
            ['ma_quyen' => 'PROFILE_EDIT', 'ten_quyen' => 'Sửa Hồ sơ cá nhân', 'module' => 'Tài khoản', 'action' => 'EDIT'],
            ['ma_quyen' => 'CHANGE_PASSWORD', 'ten_quyen' => 'Đổi mật khẩu', 'module' => 'Tài khoản', 'action' => 'CHANGE_PASSWORD'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::withTrashed()->updateOrCreate(
                ['ma_quyen' => $permissionData['ma_quyen']],
                [
                    'ten_quyen' => $permissionData['ten_quyen'],
                    'module' => $permissionData['module'],
                    'action' => $permissionData['action'],
                    'trang_thai' => true,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
