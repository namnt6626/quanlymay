@extends('layouts/contentNavbarLayout')

@section('title', 'Phân quyền vai trò')

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const moduleCards = document.querySelectorAll('[data-module-card]');

      moduleCards.forEach(function(card) {
        const selectAll = card.querySelector('[data-select-all]');
        const permissionCheckboxes = Array.from(card.querySelectorAll('[data-permission-checkbox]'));

        function syncSelectAllState() {
          if (!selectAll) {
            return;
          }

          const checkedCount = permissionCheckboxes.filter(function(checkbox) {
            return checkbox.checked;
          }).length;

          selectAll.checked = permissionCheckboxes.length > 0 && checkedCount === permissionCheckboxes.length;
          selectAll.indeterminate = checkedCount > 0 && checkedCount < permissionCheckboxes.length;
        }

        if (selectAll) {
          selectAll.addEventListener('change', function() {
            permissionCheckboxes.forEach(function(checkbox) {
              checkbox.checked = selectAll.checked;
            });
          });
        }

        permissionCheckboxes.forEach(function(checkbox) {
          checkbox.addEventListener('change', syncSelectAllState);
        });

        syncSelectAllState();
      });
    });
  </script>
@endsection

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <div>
        <h5 class="mb-1">{{ $role->ten_vai_tro }}</h5>
        <div class="text-muted small">{{ $role->ma_vai_tro }}</div>
      </div>
      <a href="{{ route('role-permission.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('role-permission.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        @php
          $selectedPermissionIds = collect(old('permissions', $assignedPermissionIds))
              ->map(fn($permissionId) => (string) $permissionId)
              ->all();
        @endphp

        <div class="row g-4">
          @foreach ($permissions as $moduleName => $modulePermissions)
            <div class="col-12" data-module-card>
              <div class="border rounded-3 p-3 h-100">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-3">
                  <div>
                    <h6 class="mb-1 text-uppercase">{{ $moduleName }}</h6>
                    <div class="text-muted small">Chọn các quyền cần gán cho vai trò</div>
                  </div>
                  <label class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" data-select-all>
                    <span class="form-check-label">Chọn tất cả</span>
                  </label>
                </div>

                <div class="row g-3">
                  @foreach ($modulePermissions as $permission)
                    @php
                      $actionLabel = match ($permission->action) {
                          'VIEW' => 'Xem',
                          'CREATE' => 'Thêm',
                          'EDIT' => 'Sửa',
                          'DELETE' => 'Xóa',
                          default => $permission->action,
                      };
                    @endphp
                    <div class="col-12 col-md-6 col-xl-3">
                      <label class="form-check d-flex align-items-center gap-2 mb-0 p-2 border rounded-2 bg-body">
                        <input class="form-check-input m-0" type="checkbox" name="permissions[]"
                          value="{{ $permission->id }}" data-permission-checkbox @checked(in_array((string) $permission->id, $selectedPermissionIds, true))>
                        <span class="form-check-label w-100 d-flex justify-content-between align-items-center">
                          <span>{{ $actionLabel }}</span>
                          <span class="badge bg-label-secondary">{{ $permission->ma_quyen }}</span>
                        </span>
                      </label>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endforeach
        </div>

        @error('permissions')
          <div class="text-danger mt-3">{{ $message }}</div>
        @enderror

        <div class="d-flex gap-2 flex-wrap mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base bx bx-save me-1"></i> Lưu phân quyền
          </button>
          <a href="{{ route('role-permission.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
      </form>
    </div>
  </div>
@endsection
