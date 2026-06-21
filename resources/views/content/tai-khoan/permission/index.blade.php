@extends('layouts/contentNavbarLayout')

@section('title', 'Quyền')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách quyền</h5>
      @if (hasPermission('PERMISSION_CREATE'))
        <a href="{{ route('permission.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('permission.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã quyền, tên quyền, module hoặc hành động">
        </div>
        @include('content.shared._per-page-select')

        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('permission.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Mã quyền</th>
            <th>Tên quyền</th>
            <th>Module</th>
            <th>Action</th>
            <th>Trạng thái</th>
            <th>Mô tả</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($permissions as $permission)
            <tr>
              <td>{{ $permissions->firstItem() + $loop->index }}</td>
              <td><strong>{{ $permission->ma_quyen }}</strong></td>
              <td>{{ $permission->ten_quyen }}</td>
              <td>{{ $permission->module }}</td>
              <td>{{ $permission->action }}</td>
              <td>
                @if ($permission->trang_thai)
                  <span class="badge bg-label-success">Hoạt động</span>
                @else
                  <span class="badge bg-label-secondary">Ngừng hoạt động</span>
                @endif
              </td>
              <td class="text-wrap">{{ $permission->mo_ta ?: '-' }}</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('PERMISSION_EDIT'))
                    <a href="{{ route('permission.edit', $permission) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('PERMISSION_DELETE'))
                    <form action="{{ route('permission.destroy', $permission) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa quyền này?');">
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
              <td colspan="8" class="text-center py-4">Chưa có dữ liệu quyền.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($permissions->hasPages())
      <div class="card-footer">
        {{ $permissions->links() }}
      </div>
    @endif
  </div>
@endsection
