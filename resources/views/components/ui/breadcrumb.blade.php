@php
  use Illuminate\Support\Facades\Route;

  $routeName = Route::currentRouteName();

  $breadcrumbMap = [
      'dashboard-analytics' => ['Dashboard'],
      'pages-account-settings-account' => ['Trang', 'Cài đặt tài khoản'],
      'pages-account-settings-notifications' => ['Trang', 'Cài đặt tài khoản'],
      'pages-account-settings-connections' => ['Trang', 'Cài đặt tài khoản'],
      'pages-misc-error' => ['Trang', 'Lỗi'],
      'pages-misc-under-maintenance' => ['Trang', 'Bảo trì'],
      'forms-basic-inputs' => ['Biểu mẫu', 'Ô nhập cơ bản'],
      'forms-input-groups' => ['Biểu mẫu', 'Nhóm nhập'],
      'form-layouts-vertical' => ['Biểu mẫu', 'Bố cục dọc'],
      'form-layouts-horizontal' => ['Biểu mẫu', 'Bố cục ngang'],
      'tables-basic' => ['Bảng', 'Bảng cơ bản'],
      'mat-hang' => ['Danh mục', 'Mã hàng'],
      'mat-hang.index' => ['Danh mục', 'Mã hàng'],
      'mat-hang.create' => ['Danh mục', 'Mã hàng'],
      'mat-hang.edit' => ['Danh mục', 'Mã hàng'],
      'mau.index' => ['Danh mục', 'Màu'],
      'mau.create' => ['Danh mục', 'Màu'],
      'mau.edit' => ['Danh mục', 'Màu'],
      'size.index' => ['Danh mục', 'Size'],
      'size.create' => ['Danh mục', 'Size'],
      'size.edit' => ['Danh mục', 'Size'],
      'don-hangs' => ['Đơn hàng', 'Danh sách'],
      'don-hangs.index' => ['Đơn hàng', 'Danh sách'],
      'don-hangs.create' => ['Đơn hàng', 'Thêm mới'],
      'don-hangs.edit' => ['Đơn hàng', 'Cập nhật'],
      'don-hangs.show' => ['Đơn hàng', 'Chi tiết'],
      'don-vi-cat.index' => ['Danh mục', 'Đơn vị cắt'],
      'don-vi-cat.create' => ['Danh mục', 'Đơn vị cắt'],
      'don-vi-cat.edit' => ['Danh mục', 'Đơn vị cắt'],
      'don-vi-may.index' => ['Danh mục', 'Đơn vị may'],
      'don-vi-may.create' => ['Danh mục', 'Đơn vị may'],
      'don-vi-may.edit' => ['Danh mục', 'Đơn vị may'],
      'cat.index' => ['Sản xuất', 'Cắt'],
      'cat.create' => ['Sản xuất', 'Cắt'],
      'cat.edit' => ['Sản xuất', 'Cắt'],
      'phan-bo-may.index' => ['Sản xuất', 'Phân bổ may'],
      'phan-bo-may.create' => ['Sản xuất', 'Phân bổ may'],
      'phan-bo-may.edit' => ['Sản xuất', 'Phân bổ may'],
      'qc.index' => ['Sản xuất', 'QC'],
      'qc.create' => ['Sản xuất', 'QC'],
      'qc.edit' => ['Sản xuất', 'QC'],
      'nhap-kho.index' => ['Kho', 'Nhập kho'],
      'nhap-kho.create' => ['Kho', 'Nhập kho'],
      'nhap-kho.edit' => ['Kho', 'Nhập kho'],
      'xuat-kho.index' => ['Kho', 'Xuất kho'],
      'xuat-kho.create' => ['Kho', 'Xuất kho'],
      'xuat-kho.edit' => ['Kho', 'Xuất kho'],
      'ton-kho.index' => ['Kho', 'Tồn kho'],
      'bao-cao.tong-hop-don-hang' => ['Báo cáo', 'Tổng hợp đơn hàng'],
      'role.index' => ['Tài khoản', 'Vai trò'],
      'role.create' => ['Tài khoản', 'Vai trò'],
      'role.edit' => ['Tài khoản', 'Vai trò'],
      'permission.index' => ['Tài khoản', 'Quyền'],
      'permission.create' => ['Tài khoản', 'Quyền'],
      'permission.edit' => ['Tài khoản', 'Quyền'],
      'role-permission.index' => ['Tài khoản', 'Phân quyền'],
      'role-permission.edit' => ['Tài khoản', 'Phân quyền'],
      'user.index' => ['Tài khoản', 'Người dùng'],
      'user.create' => ['Tài khoản', 'Người dùng'],
      'user.edit' => ['Tài khoản', 'Người dùng'],
      'profile.index' => ['Tài khoản', 'Hồ sơ cá nhân'],
      'profile.change-password' => ['Tài khoản', 'Đổi mật khẩu'],
  ];

  $breadcrumb = $breadcrumbMap[$routeName] ?? null;
@endphp

@if ($breadcrumb && $routeName !== 'dashboard-analytics')
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      @if (count($breadcrumb) === 1)
        <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb[0] }}</li>
      @else
        <li class="breadcrumb-item">{{ $breadcrumb[0] }}</li>
        <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb[1] }}</li>
      @endif
    </ol>
  </nav>
@endif
