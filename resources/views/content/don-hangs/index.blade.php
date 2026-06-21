@extends('layouts/contentNavbarLayout')

@section('title', 'Đơn hàng')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách đơn hàng</h5>
      @if (hasPermission('DON_HANG_CREATE'))
        <a href="{{ route('don-hangs.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('don-hangs.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl-4">
          <label class="form-label" for="q">Từ khóa</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn, mã KH hoặc kênh bán">
        </div>
        <div class="col-6 col-md-3 col-xl-2">
          <label class="form-label" for="ngay_nhan_from">Ngày nhận từ</label>
          <input type="date" class="form-control" id="ngay_nhan_from" name="ngay_nhan_from"
            value="{{ $ngayNhanFrom }}">
        </div>
        <div class="col-6 col-md-3 col-xl-2">
          <label class="form-label" for="ngay_nhan_to">Ngày nhận đến</label>
          <input type="date" class="form-control" id="ngay_nhan_to" name="ngay_nhan_to" value="{{ $ngayNhanTo }}">
        </div>
        <div class="col-6 col-md-3 col-xl-2">
          <label class="form-label" for="han_giao_from">Hạn giao từ</label>
          <input type="date" class="form-control" id="han_giao_from" name="han_giao_from" value="{{ $hanGiaoFrom }}">
        </div>
        <div class="col-6 col-md-3 col-xl-2">
          <label class="form-label" for="han_giao_to">Hạn giao đến</label>
          <input type="date" class="form-control" id="han_giao_to" name="han_giao_to" value="{{ $hanGiaoTo }}">
        </div>
        @include('content.shared._per-page-select')

        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('don-hangs.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">#</th>
            <th>Ngày nhận</th>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Hạn giao</th>
            <th>Kênh bán</th>
            <th class="text-end">Tổng SL đặt</th>
            <th class="text-end">Số dòng chi tiết</th>
            <th style="width: 160px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($donHangs as $donHang)
            <tr>
              <td>{{ $donHangs->firstItem() + $loop->index }}</td>
              <td>{{ optional($donHang->ngay_nhan)->format('d/m/Y') ?? '-' }}</td>
              <td><strong>{{ $donHang->ma_don }}</strong></td>
              <td>{{ $donHang->ma_kh }}</td>
              <td>{{ optional($donHang->han_giao)->format('d/m/Y') ?? '-' }}</td>
              <td>{{ $donHang->kenh_ban ?: '-' }}</td>
              <td class="text-end">{{ formatPhanBoNumber($donHang->tong_so_luong_dat ?? 0) }}</td>
              <td class="text-end">{{ number_format((int) ($donHang->so_dong_chi_tiet ?? 0), 0, ',', '.') }}</td>
              <td>
                <div class="d-flex gap-2 flex-wrap">
                  @if (hasPermission('DON_HANG_VIEW'))
                    <a href="{{ route('don-hangs.show', $donHang) }}" class="btn btn-sm btn-icon btn-outline-info"
                      title="Chi tiết">
                      <i class="icon-base bx bx-detail"></i>
                    </a>
                  @endif
                  @if (hasPermission('DON_HANG_UPDATE'))
                    <a href="{{ route('don-hangs.edit', $donHang) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('DON_HANG_DELETE'))
                    <form action="{{ route('don-hangs.destroy', $donHang) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa đơn hàng này?');">
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
              <td colspan="9" class="text-center py-4">Chưa có dữ liệu đơn hàng.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($donHangs->hasPages())
      <div class="card-footer">
        {{ $donHangs->links() }}
      </div>
    @endif
  </div>
@endsection
