@extends('layouts/contentNavbarLayout')

@section('title', 'Danh mục đơn vị may')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh mục đơn vị may</h5>
      @if (hasPermission('DANH_MUC_CREATE'))
        <a href="{{ route('don-vi-may.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('don-vi-may.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-lg">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn vị hoặc tên đơn vị">
        </div>
        <div class="col-12 col-lg-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('don-vi-may.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Mã đơn vị</th>
            <th>Tên đơn vị</th>
            <th>Trạng thái</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($donViMays as $donViMay)
            <tr>
              <td>{{ $donViMays->firstItem() + $loop->index }}</td>
              <td><strong>{{ $donViMay->ma_don_vi }}</strong></td>
              <td>{{ $donViMay->ten_don_vi }}</td>
              <td>
                @if ($donViMay->trang_thai)
                  <span class="badge bg-label-success">Hoạt động</span>
                @else
                  <span class="badge bg-label-secondary">Ngừng dùng</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('DANH_MUC_EDIT'))
                    <a href="{{ route('don-vi-may.edit', $donViMay) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('DANH_MUC_DELETE'))
                    <form action="{{ route('don-vi-may.destroy', $donViMay) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa đơn vị may này?');">
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
              <td colspan="5" class="text-center py-4">Chưa có dữ liệu đơn vị may.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($donViMays->hasPages())
      <div class="card-footer">
        {{ $donViMays->links() }}
      </div>
    @endif
  </div>
@endsection
