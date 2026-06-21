@extends('layouts/contentNavbarLayout')

@section('title', 'Vai trò')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách vai trò</h5>
      @if (hasPermission('ROLE_CREATE'))
        <a href="{{ route('role.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('role.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã vai trò hoặc tên vai trò">
        </div>
        @include('content.shared._per-page-select')

        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('role.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Mã vai trò</th>
            <th>Tên vai trò</th>
            <th>Mô tả</th>
            <th>Trạng thái</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($roles as $role)
            <tr>
              <td>{{ $roles->firstItem() + $loop->index }}</td>
              <td><strong>{{ $role->ma_vai_tro }}</strong></td>
              <td>{{ $role->ten_vai_tro }}</td>
              <td class="text-wrap">{{ $role->mo_ta ?: '-' }}</td>
              <td>
                @if ($role->trang_thai)
                  <span class="badge bg-label-success">Hoạt động</span>
                @else
                  <span class="badge bg-label-secondary">Ngừng hoạt động</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('ROLE_EDIT'))
                    <a href="{{ route('role.edit', $role) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('ROLE_DELETE'))
                    <form action="{{ route('role.destroy', $role) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa vai trò này?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Xóa">
                        <i class="icon-base bx bx-trash"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4">Chưa có dữ liệu vai trò.</td>
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
