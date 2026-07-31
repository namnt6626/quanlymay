<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\layouts\WithoutMenu;
use App\Http\Controllers\layouts\WithoutNavbar;
use App\Http\Controllers\layouts\Fluid;
use App\Http\Controllers\layouts\Container;
use App\Http\Controllers\layouts\Blank;
use App\Http\Controllers\pages\AccountSettingsAccount;
use App\Http\Controllers\pages\AccountSettingsNotifications;
use App\Http\Controllers\pages\AccountSettingsConnections;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\pages\MiscUnderMaintenance;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\BaoCao\BaoCaoTongHopDonHangController;
use App\Http\Controllers\BaoCao\BaoCaoBanHangOnlineController;
use App\Http\Controllers\DonHangOnline\HangHoanOnlineController;
use App\Http\Controllers\DanhMuc\KenhBanController;
use App\Http\Controllers\DanhMuc\MatHangController;
use App\Http\Controllers\DanhMuc\MauController;
use App\Http\Controllers\DanhMuc\SizeController;
use App\Http\Controllers\DonHangOnline\NhapHangOnlineController;
use App\Http\Controllers\DonHangOnline\TonKhoOnlineController;
use App\Http\Controllers\DanhMuc\DonViCatController;
use App\Http\Controllers\DanhMuc\DonViMayController;
use App\Http\Controllers\DonHang\DonHangController;
use App\Http\Controllers\SanXuat\CatController;
use App\Http\Controllers\SanXuat\DonHangHoanThanhController;
use App\Http\Controllers\SanXuat\NhapKhoController;
use App\Http\Controllers\SanXuat\PhanBoMayController;
use App\Http\Controllers\SanXuat\PhieuXuatKhoController;
use App\Http\Controllers\SanXuat\ProfitAnalysisController;
use App\Http\Controllers\SanXuat\QcController;
use App\Http\Controllers\SanXuat\TonKhoController;
use App\Http\Controllers\TaiKhoan\RolePermissionController;
use App\Http\Controllers\TaiKhoan\PermissionController;
use App\Http\Controllers\TaiKhoan\RoleController;
use App\Http\Controllers\TaiKhoan\ActivityLogController;
use App\Http\Controllers\form_elements\BasicInput;
use App\Http\Controllers\form_elements\InputGroups;
use App\Http\Controllers\form_layouts\VerticalForm;
use App\Http\Controllers\form_layouts\HorizontalForm;
use App\Http\Controllers\tables\Basic as TablesBasic;

Route::middleware('auth')->group(function () {
  // Main Page Route
  Route::get('/', [Analytics::class, 'index'])->middleware('permission:DASHBOARD_VIEW')->name('dashboard-analytics');

  // pages
  Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('pages-account-settings-account');
  Route::get('/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('pages-account-settings-notifications');
  Route::get('/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('pages-account-settings-connections');
  Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');
  Route::get('/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('pages-misc-under-maintenance');

  // form elements
  Route::get('/forms/basic-inputs', [BasicInput::class, 'index'])->name('forms-basic-inputs');
  Route::get('/forms/input-groups', [InputGroups::class, 'index'])->name('forms-input-groups');

  // form layouts
  Route::get('/form/layouts-vertical', [VerticalForm::class, 'index'])->name('form-layouts-vertical');
  Route::get('/form/layouts-horizontal', [HorizontalForm::class, 'index'])->name('form-layouts-horizontal');

  // tables
  Route::get('/tables/basic', [TablesBasic::class, 'index'])->name('tables-basic');
});

$registerCrudRoutes = function (string $uri, string $controller, string $parameterName, array $permissions): void {
  Route::get($uri, [$controller, 'index'])->middleware(['auth', 'permission:' . $permissions['view']])->name($uri . '.index');

  if (isset($permissions['create'])) {
    Route::get($uri . '/create', [$controller, 'create'])->middleware(['auth', 'permission:' . $permissions['create']])->name($uri . '.create');
    Route::post($uri, [$controller, 'store'])->middleware(['auth', 'permission:' . $permissions['create']])->name($uri . '.store');
  }

  if (isset($permissions['edit'])) {
    Route::get($uri . '/{' . $parameterName . '}/edit', [$controller, 'edit'])->middleware(['auth', 'permission:' . $permissions['edit']])->name($uri . '.edit');
    Route::put($uri . '/{' . $parameterName . '}', [$controller, 'update'])->middleware(['auth', 'permission:' . $permissions['edit']])->name($uri . '.update');
  }

  if (isset($permissions['delete'])) {
    Route::delete($uri . '/{' . $parameterName . '}', [$controller, 'destroy'])->middleware(['auth', 'permission:' . $permissions['delete']])->name($uri . '.destroy');
  }
};

$registerIndexRoute = function (string $uri, string $controller, array $permissions): void {
  Route::get($uri, [$controller, 'index'])->middleware(['auth', 'permission:' . $permissions['view']])->name($uri . '.index');
};

$registerCrudRoutes('mat-hang', MatHangController::class, 'mat_hang', [
  'view' => 'DANH_MUC_VIEW',
  'create' => 'DANH_MUC_CREATE',
  'edit' => 'DANH_MUC_EDIT',
  'delete' => 'DANH_MUC_DELETE',
]);
$registerCrudRoutes('mau', MauController::class, 'mau', [
  'view' => 'DANH_MUC_VIEW',
  'create' => 'DANH_MUC_CREATE',
  'edit' => 'DANH_MUC_EDIT',
  'delete' => 'DANH_MUC_DELETE',
]);
$registerCrudRoutes('size', SizeController::class, 'size', [
  'view' => 'DANH_MUC_VIEW',
  'create' => 'DANH_MUC_CREATE',
  'edit' => 'DANH_MUC_EDIT',
  'delete' => 'DANH_MUC_DELETE',
]);
$registerCrudRoutes('kenh-ban', KenhBanController::class, 'kenh_ban', [
  'view' => 'DANH_MUC_VIEW',
  'create' => 'DANH_MUC_CREATE',
  'edit' => 'DANH_MUC_EDIT',
  'delete' => 'DANH_MUC_DELETE',
]);
$registerCrudRoutes('don-vi-cat', DonViCatController::class, 'don_vi_cat', [
  'view' => 'DANH_MUC_VIEW',
  'create' => 'DANH_MUC_CREATE',
  'edit' => 'DANH_MUC_EDIT',
  'delete' => 'DANH_MUC_DELETE',
]);
$registerCrudRoutes('don-vi-may', DonViMayController::class, 'don_vi_may', [
  'view' => 'DANH_MUC_VIEW',
  'create' => 'DANH_MUC_CREATE',
  'edit' => 'DANH_MUC_EDIT',
  'delete' => 'DANH_MUC_DELETE',
]);
Route::delete('cat/bulk', [CatController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:CAT_DELETE'])
  ->name('cat.bulk-destroy');
Route::delete('phan-bo-may/bulk', [PhanBoMayController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:PHAN_BO_MAY_DELETE'])
  ->name('phan-bo-may.bulk-destroy');
Route::delete('qc/bulk', [QcController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:QC_DELETE'])
  ->name('qc.bulk-destroy');
Route::delete('don-hang-hoan-thanh/bulk', [DonHangHoanThanhController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_DELETE'])
  ->name('don-hang-hoan-thanh.bulk-destroy');
Route::delete('nhap-hang-online/bulk', [NhapHangOnlineController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_DELETE'])
  ->name('nhap-hang-online.bulk-destroy');
Route::delete('hang-hoan-online/bulk', [HangHoanOnlineController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_DELETE'])
  ->name('hang-hoan-online.bulk-destroy');
Route::get('hang-hoan-online/import', [HangHoanOnlineController::class, 'importForm'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_CREATE'])
  ->name('hang-hoan-online.import');
Route::post('hang-hoan-online/import/preview', [HangHoanOnlineController::class, 'preview'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_CREATE'])
  ->name('hang-hoan-online.preview');
Route::post('hang-hoan-online/import/commit', [HangHoanOnlineController::class, 'commit'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_CREATE'])
  ->name('hang-hoan-online.commit');
Route::delete('xuat-kho/bulk', [PhieuXuatKhoController::class, 'bulkDestroy'])
  ->middleware(['auth', 'permission:XUAT_KHO_DELETE'])
  ->name('xuat-kho.bulk-destroy');

$registerCrudRoutes('cat', CatController::class, 'cat', [
  'view' => 'CAT_VIEW',
  'create' => 'CAT_CREATE',
  'edit' => 'CAT_EDIT',
  'delete' => 'CAT_DELETE',
]);
$registerCrudRoutes('phan-bo-may', PhanBoMayController::class, 'phan_bo_may', [
  'view' => 'PHAN_BO_MAY_VIEW',
  'create' => 'PHAN_BO_MAY_CREATE',
  'edit' => 'PHAN_BO_MAY_EDIT',
  'delete' => 'PHAN_BO_MAY_DELETE',
]);
$registerCrudRoutes('qc', QcController::class, 'qc', [
  'view' => 'QC_VIEW',
  'create' => 'QC_CREATE',
  'edit' => 'QC_EDIT',
  'delete' => 'QC_DELETE',
]);
Route::get('phan-tich-lai-lo', [ProfitAnalysisController::class, 'index'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_VIEW'])
  ->name('phan-tich-lai-lo.index');
Route::get('phan-tich-lai-lo/import', [ProfitAnalysisController::class, 'create'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_CREATE'])
  ->name('phan-tich-lai-lo.create');
Route::get('phan-tich-lai-lo/import/{marketplace}', [ProfitAnalysisController::class, 'create'])
  ->whereIn('marketplace', ['tiktok', 'shopee'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_CREATE'])
  ->name('phan-tich-lai-lo.create-marketplace');
Route::post('phan-tich-lai-lo/import/upload-file', [ProfitAnalysisController::class, 'uploadFile'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_CREATE'])
  ->name('phan-tich-lai-lo.upload-file');
Route::post('phan-tich-lai-lo/import/preview', [ProfitAnalysisController::class, 'preview'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_CREATE'])
  ->name('phan-tich-lai-lo.preview');
Route::post('phan-tich-lai-lo/import/commit', [ProfitAnalysisController::class, 'commit'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_CREATE'])
  ->name('phan-tich-lai-lo.commit');
Route::post('phan-tich-lai-lo/shops', [ProfitAnalysisController::class, 'storeShop'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_CREATE'])
  ->name('phan-tich-lai-lo.shops.store');
Route::put('phan-tich-lai-lo/shops/{profitAnalysisShop}', [ProfitAnalysisController::class, 'updateShop'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_EDIT'])
  ->name('phan-tich-lai-lo.shops.update');
Route::patch('phan-tich-lai-lo/shops/{profitAnalysisShop}/toggle', [ProfitAnalysisController::class, 'toggleShop'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_EDIT'])
  ->name('phan-tich-lai-lo.shops.toggle');
Route::get('phan-tich-lai-lo/{profitAnalysisPeriod}/edit', [ProfitAnalysisController::class, 'edit'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_EDIT'])
  ->name('phan-tich-lai-lo.edit');
Route::put('phan-tich-lai-lo/{profitAnalysisPeriod}', [ProfitAnalysisController::class, 'update'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_EDIT'])
  ->name('phan-tich-lai-lo.update');
Route::delete('phan-tich-lai-lo/{profitAnalysisPeriod}', [ProfitAnalysisController::class, 'destroy'])
  ->middleware(['auth', 'permission:PHAN_TICH_LAI_LO_DELETE'])
  ->name('phan-tich-lai-lo.destroy');
Route::get('don-hang-hoan-thanh/import', [DonHangHoanThanhController::class, 'importForm'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_CREATE'])->name('don-hang-hoan-thanh.import');
Route::post('don-hang-hoan-thanh/import/preview', [DonHangHoanThanhController::class, 'preview'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_CREATE'])->name('don-hang-hoan-thanh.preview');
Route::post('don-hang-hoan-thanh/import/commit', [DonHangHoanThanhController::class, 'commit'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_CREATE'])->name('don-hang-hoan-thanh.commit');
$registerCrudRoutes('don-hang-hoan-thanh', DonHangHoanThanhController::class, 'don_hang_hoan_thanh', [
  'view' => 'DON_HANG_HOAN_THANH_VIEW',
  'create' => 'DON_HANG_HOAN_THANH_CREATE',
  'edit' => 'DON_HANG_HOAN_THANH_EDIT',
  'delete' => 'DON_HANG_HOAN_THANH_DELETE',
]);
$registerCrudRoutes('nhap-hang-online', NhapHangOnlineController::class, 'nhap_hang_online', [
  'view' => 'DON_HANG_HOAN_THANH_VIEW',
  'create' => 'DON_HANG_HOAN_THANH_CREATE',
  'edit' => 'DON_HANG_HOAN_THANH_EDIT',
  'delete' => 'DON_HANG_HOAN_THANH_DELETE',
]);
$registerCrudRoutes('hang-hoan-online', HangHoanOnlineController::class, 'hang_hoan_online', [
  'view' => 'DON_HANG_HOAN_THANH_VIEW',
  'create' => 'DON_HANG_HOAN_THANH_CREATE',
  'edit' => 'DON_HANG_HOAN_THANH_EDIT',
  'delete' => 'DON_HANG_HOAN_THANH_DELETE',
]);
$registerCrudRoutes('nhap-kho', NhapKhoController::class, 'nhap_kho', [
  'view' => 'NHAP_KHO_VIEW',
  'create' => 'NHAP_KHO_CREATE',
  'edit' => 'NHAP_KHO_EDIT',
  'delete' => 'NHAP_KHO_DELETE',
]);
$registerCrudRoutes('xuat-kho', PhieuXuatKhoController::class, 'phieu_xuat_kho', [
  'view' => 'XUAT_KHO_VIEW',
  'create' => 'XUAT_KHO_CREATE',
  'edit' => 'XUAT_KHO_EDIT',
  'delete' => 'XUAT_KHO_DELETE',
]);
$registerCrudRoutes('don-hangs', DonHangController::class, 'don_hang', [
  'view' => 'DON_HANG_VIEW',
  'create' => 'DON_HANG_CREATE',
  'edit' => 'DON_HANG_UPDATE',
  'delete' => 'DON_HANG_DELETE',
]);
Route::get('don-hangs/{don_hang}', [DonHangController::class, 'show'])
  ->middleware(['auth', 'permission:DON_HANG_VIEW'])
  ->name('don-hangs.show');
$registerIndexRoute('ton-kho', TonKhoController::class, [
  'view' => 'TON_KHO_VIEW',
]);
$registerIndexRoute('ton-kho-online', TonKhoOnlineController::class, [
  'view' => 'DON_HANG_HOAN_THANH_VIEW',
]);
Route::post('ton-kho-online/product-groups', [TonKhoOnlineController::class, 'storeProductGroup'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_VIEW'])
  ->name('ton-kho-online.product-groups.store');
Route::delete('ton-kho-online/product-groups', [TonKhoOnlineController::class, 'destroyProductGroup'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_VIEW'])
  ->name('ton-kho-online.product-groups.destroy');
Route::post('ton-kho-online/color-groups', [TonKhoOnlineController::class, 'storeColorGroup'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_VIEW'])
  ->name('ton-kho-online.color-groups.store');
Route::delete('ton-kho-online/color-groups', [TonKhoOnlineController::class, 'destroyColorGroup'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_VIEW'])
  ->name('ton-kho-online.color-groups.destroy');
Route::post('ton-kho-online/size-groups', [TonKhoOnlineController::class, 'storeSizeGroup'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_VIEW'])
  ->name('ton-kho-online.size-groups.store');
Route::delete('ton-kho-online/size-groups', [TonKhoOnlineController::class, 'destroySizeGroup'])
  ->middleware(['auth', 'permission:DON_HANG_HOAN_THANH_VIEW'])
  ->name('ton-kho-online.size-groups.destroy');
Route::get('bao-cao/tong-hop-don-hang', BaoCaoTongHopDonHangController::class)
  ->middleware(['auth', 'permission:BAO_CAO_TONG_HOP_DON_HANG_VIEW'])
  ->name('bao-cao.tong-hop-don-hang');
Route::get('bao-cao/ban-hang-online', BaoCaoBanHangOnlineController::class)
  ->middleware(['auth', 'permission:BAO_CAO_BAN_HANG_ONLINE_VIEW'])
  ->name('bao-cao.ban-hang-online');
$registerCrudRoutes('role', RoleController::class, 'role', [
  'view' => 'ROLE_VIEW',
  'create' => 'ROLE_CREATE',
  'edit' => 'ROLE_EDIT',
  'delete' => 'ROLE_DELETE',
]);
$registerCrudRoutes('permission', PermissionController::class, 'permission', [
  'view' => 'PERMISSION_VIEW',
  'create' => 'PERMISSION_CREATE',
  'edit' => 'PERMISSION_EDIT',
  'delete' => 'PERMISSION_DELETE',
]);
$registerCrudRoutes('user', \App\Http\Controllers\TaiKhoan\UserController::class, 'user', [
  'view' => 'USER_VIEW',
  'create' => 'USER_CREATE',
  'edit' => 'USER_EDIT',
  'delete' => 'USER_DELETE',
]);

Route::get('role-permission', [\App\Http\Controllers\TaiKhoan\RolePermissionController::class, 'index'])->middleware(['auth', 'permission:ROLE_PERMISSION_VIEW'])->name('role-permission.index');
Route::get('role-permission/{role}/edit', [\App\Http\Controllers\TaiKhoan\RolePermissionController::class, 'edit'])->middleware(['auth', 'permission:ROLE_PERMISSION_EDIT'])->name('role-permission.edit');
Route::put('role-permission/{role}', [\App\Http\Controllers\TaiKhoan\RolePermissionController::class, 'update'])->middleware(['auth', 'permission:ROLE_PERMISSION_EDIT'])->name('role-permission.update');
Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware(['auth', 'permission:ACTIVITY_LOG_VIEW'])->name('activity-logs.index');
Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->middleware(['auth', 'permission:ACTIVITY_LOG_VIEW'])->name('activity-logs.show');

Route::middleware('guest')->group(function () {
  Route::get('/login', [LoginBasic::class, 'index'])->name('login');
  Route::post('/login', [LoginBasic::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
  Route::post('/logout', [LoginBasic::class, 'destroy'])->name('logout');
  Route::get('/profile', [\App\Http\Controllers\authentications\ProfileController::class, 'index'])->middleware('permission:PROFILE_VIEW')->name('profile.index');
  Route::put('/profile', [\App\Http\Controllers\authentications\ProfileController::class, 'update'])->middleware('permission:PROFILE_EDIT')->name('profile.update');
  Route::post('/profile/run-migrations', [\App\Http\Controllers\authentications\ProfileController::class, 'runMigrations'])->middleware('permission:PROFILE_VIEW')->name('profile.run-migrations');
  Route::get('/profile/change-password', [\App\Http\Controllers\authentications\ProfileController::class, 'changePassword'])->middleware('permission:CHANGE_PASSWORD')->name('profile.change-password');
  Route::put('/profile/change-password', [\App\Http\Controllers\authentications\ProfileController::class, 'updatePassword'])->middleware('permission:CHANGE_PASSWORD')->name('profile.update-password');
});

// authentication
Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('auth-login-basic');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('auth-register-basic');
Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('auth-reset-password-basic');
