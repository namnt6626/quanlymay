@extends('layouts/contentNavbarLayout')

@section('title', 'Phân quyền vai trò')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách vai trò</h5>
      <span class="text-muted small">Chọn vai trò để gán nhiều quyền</span>
    </div>

    <div class="card-body">
      <form action="{{ route('role-permission.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã vai trò hoặc tên vai trò">
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('role-permission.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Mã vai trò</th>
            <th>Tên vai trò</th>
            <th>Số lượng quyền</th>
            <th style="width: 140px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($roles as $role)
            <tr>
              <td>{{ $roles->firstItem() + $loop->index }}</td>
              <td><strong>{{ $role->ma_vai_tro }}</strong></td>
              <td>{{ $role->ten_vai_tro }}</td>
              <td>{{ $role->permissions_count }} quyền</td>
              <td>
                @if (hasPermission('ROLE_PERMISSION_EDIT'))
                  <a href="{{ route('role-permission.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                    <i class="icon-base bx bx-lock-open me-1"></i> Phân quyền
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4">Chưa có dữ liệu vai trò.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($roles->hasPages())
      <div class="card-footer">
        {{ $roles->links() }}
      </div>
    @endif
  </div>
@endsection
