@extends('layouts/contentNavbarLayout')

@section('title', 'Danh mục mã hàng')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh mục mã hàng</h5>
      @if (hasPermission('DANH_MUC_CREATE'))
        <a href="{{ route('mat-hang.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('mat-hang.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã hàng, tên hàng hoặc mô tả">
        </div>
        @include('content.shared._per-page-select')

        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('mat-hang.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">#</th>
            <th>Mã hàng</th>
            <th>Tên hàng</th>
            <th>Mô tả</th>
            <th>Trạng thái</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($matHangs as $matHang)
            <tr>
              <td>{{ $matHangs->firstItem() + $loop->index }}</td>
              <td><strong>{{ $matHang->ma_hang }}</strong></td>
              <td>{{ $matHang->ten_hang }}</td>
              <td class="text-wrap">{{ $matHang->mo_ta ?: '-' }}</td>
              <td>
                @if ($matHang->trang_thai)
                  <span class="badge bg-label-success">Hoạt động</span>
                @else
                  <span class="badge bg-label-secondary">Ngừng dùng</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('DANH_MUC_EDIT'))
                    <a href="{{ route('mat-hang.edit', $matHang) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('DANH_MUC_DELETE'))
                    <form action="{{ route('mat-hang.destroy', $matHang) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa mã hàng này?');">
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
              <td colspan="6" class="text-center py-4">Chưa có dữ liệu mã hàng.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($matHangs->hasPages())
      <div class="card-footer">
        {{ $matHangs->links() }}
      </div>
    @endif
  </div>
@endsection
