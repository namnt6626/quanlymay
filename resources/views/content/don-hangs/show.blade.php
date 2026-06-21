@extends('layouts/contentNavbarLayout')

@section('title', 'Chi tiết đơn hàng')

@section('content')
  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <div>
        <h5 class="mb-0">Chi tiết đơn hàng</h5>
        <div class="text-muted small">Mã đơn: <strong>{{ $donHang->ma_don }}</strong></div>
      </div>
      <a href="{{ route('don-hangs.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="text-muted small">Ngày nhận</div>
          <div class="fw-semibold">{{ optional($donHang->ngay_nhan)->format('d/m/Y') ?? '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Mã KH</div>
          <div class="fw-semibold">{{ $donHang->ma_kh }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Hạn giao</div>
          <div class="fw-semibold">{{ optional($donHang->han_giao)->format('d/m/Y') ?? '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Kênh bán</div>
          <div class="fw-semibold">{{ $donHang->kenh_ban ?: '-' }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Tổng SL đặt</div>
          <div class="fw-semibold">{{ formatPhanBoNumber($tongSoLuongDat) }}</div>
        </div>
        <div class="col-md-4">
          <div class="text-muted small">Số dòng chi tiết</div>
          <div class="fw-semibold">{{ $donHang->chiTiets->count() }}</div>
        </div>
        <div class="col-12">
          <div class="text-muted small">Ghi chú</div>
          <div class="fw-semibold text-wrap">{{ $donHang->ghi_chu ?: '-' }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Danh sách chi tiết</h5>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">#</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">SL đặt</th>
            <th>Ghi chú</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($donHang->chiTiets as $chiTiet)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>
                <strong>{{ $chiTiet->matHang?->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $chiTiet->matHang?->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $chiTiet->mau?->ten_mau ?? '-' }}</td>
              <td>{{ $chiTiet->size?->ten_size ?? '-' }}</td>
              <td class="text-end">{{ formatPhanBoNumber($chiTiet->so_luong_dat) }}</td>
              <td class="text-wrap">{{ $chiTiet->ghi_chu ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4">Chưa có chi tiết đơn hàng.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
