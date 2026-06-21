@extends('layouts/contentNavbarLayout')

@section('title', 'Danh mục size')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh mục size</h5>
      @if (hasPermission('DANH_MUC_CREATE'))
        <a href="{{ route('size.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('size.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã size hoặc tên size">
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('size.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Mã size</th>
            <th>Tên size</th>
            <th>Trạng thái</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($sizes as $size)
            <tr>
              <td>{{ $sizes->firstItem() + $loop->index }}</td>
              <td><strong>{{ $size->ma_size }}</strong></td>
              <td>{{ $size->ten_size }}</td>
              <td>
                @if ($size->trang_thai)
                  <span class="badge bg-label-success">Hoạt động</span>
                @else
                  <span class="badge bg-label-secondary">Ngừng dùng</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('DANH_MUC_EDIT'))
                    <a href="{{ route('size.edit', $size) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('DANH_MUC_DELETE'))
                    <form action="{{ route('size.destroy', $size) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa size này?');">
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
              <td colspan="5" class="text-center py-4">Chưa có dữ liệu size.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($sizes->hasPages())
      <div class="card-footer">
        {{ $sizes->links() }}
      </div>
    @endif
  </div>
@endsection
