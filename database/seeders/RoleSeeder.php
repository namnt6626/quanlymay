<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
  public function run(): void
  {
    $roles = [
      [
        'ma_vai_tro' => 'ADMIN',
        'ten_vai_tro' => 'Quản trị hệ thống',
        'mo_ta' => 'Vai trò quản trị toàn hệ thống.',
        'trang_thai' => true,
      ],
      [
        'ma_vai_tro' => 'CAT',
        'ten_vai_tro' => 'Nhân viên cắt',
        'mo_ta' => 'Vai trò dành cho nhân viên cắt.',
        'trang_thai' => true,
      ],
      [
        'ma_vai_tro' => 'MAY',
        'ten_vai_tro' => 'Nhân viên phân bổ may',
        'mo_ta' => 'Vai trò dành cho nhân viên phân bổ may.',
        'trang_thai' => true,
      ],
      [
        'ma_vai_tro' => 'QC',
        'ten_vai_tro' => 'Nhân viên QC',
        'mo_ta' => 'Vai trò dành cho nhân viên QC.',
        'trang_thai' => true,
      ],
      [
        'ma_vai_tro' => 'KHO',
        'ten_vai_tro' => 'Nhân viên kho',
        'mo_ta' => 'Vai trò dành cho nhân viên kho.',
        'trang_thai' => true,
      ],
      [
        'ma_vai_tro' => 'LANH_DAO',
        'ten_vai_tro' => 'Lãnh đạo',
        'mo_ta' => 'Vai trò dành cho lãnh đạo.',
        'trang_thai' => true,
      ],
      [
        'ma_vai_tro' => 'NHAN_VIEN',
        'ten_vai_tro' => 'Nhân viên thông thường',
        'mo_ta' => 'Vai trò dành cho nhân viên thông thường.',
        'trang_thai' => true,
      ],
    ];

    foreach ($roles as $roleData) {
      Role::withTrashed()->updateOrCreate(
        ['ma_vai_tro' => $roleData['ma_vai_tro']],
        [
          'ten_vai_tro' => $roleData['ten_vai_tro'],
          'mo_ta' => $roleData['mo_ta'],
          'trang_thai' => $roleData['trang_thai'],
          'deleted_at' => null,
        ]
      );
    }
  }
}
