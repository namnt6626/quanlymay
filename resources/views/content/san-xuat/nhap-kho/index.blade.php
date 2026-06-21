@extends('layouts/contentNavbarLayout')

@section('title', 'Nhập kho')

@section('page-style')
  <style>
    .nhap-kho-table {
      min-width: 1180px;
    }

    .nhap-kho-table thead th {
      background-color: var(--bs-gray-100);
      color: var(--bs-heading-color);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .nhap-kho-table tbody td {
      vertical-align: middle;
    }

    .nhap-kho-table .col-product {
      min-width: 180px;
    }

    .nhap-kho-table .col-unit {
      min-width: 150px;
    }

    .nhap-kho-table .col-number {
      min-width: 110px;
      white-space: nowrap;
    }

    .nhap-kho-table .col-date,
    .nhap-kho-table .col-code {
      white-space: nowrap;
    }
  </style>
@endsection

@section('content')
  @include('content.danh-muc._toast')

  @php
    $formatPhanBoNumber =
        $formatPhanBoNumber ??
        function ($value) {
            if (function_exists('formatPhanBoNumber')) {
                return formatPhanBoNumber($value);
            }

            if ($value === null || $value === '') {
                return '-';
            }

            $number = (float) $value;

            if (floor($number) == $number) {
                return number_format($number, 0, ',', '.');
            }

            return rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');
        };
  @endphp

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách nhập kho</h5>
    </div>

    <div class="card-body">
      <form action="{{ route('nhap-kho.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn, mã KH, mã hàng, tên hàng, màu, size hoặc đơn vị may">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="tu_ngay">Từ ngày</label>
          <input type="date" class="form-control" id="tu_ngay" name="tu_ngay" value="{{ $tuNgay }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="den_ngay">Đến ngày</label>
          <input type="date" class="form-control" id="den_ngay" name="den_ngay" value="{{ $denNgay }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ma_don">Mã đơn</label>
          <input type="text" class="form-control" id="ma_don" name="ma_don" value="{{ $maDon }}"
            placeholder="Mã đơn">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ma_kh">Mã KH</label>
          <input type="text" class="form-control" id="ma_kh" name="ma_kh" value="{{ $maKh }}"
            placeholder="Mã KH">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ma_hang">Mã hàng</label>
          <input type="text" class="form-control" id="ma_hang" name="ma_hang" value="{{ $maHang }}"
            placeholder="Mã/tên hàng">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="mau">Màu</label>
          <input type="text" class="form-control" id="mau" name="mau" value="{{ $mau }}" placeholder="Màu">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="size">Size</label>
          <input type="text" class="form-control" id="size" name="size" value="{{ $size }}" placeholder="Size">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="loai_ton">Loại tồn</label>
          <select class="form-select" id="loai_ton" name="loai_ton">
            <option value="">Tất cả</option>
            <option value="dat" @selected($loaiTon === 'dat')>Đạt</option>
            <option value="loi" @selected($loaiTon === 'loi')>Lỗi</option>
            <option value="hong" @selected($loaiTon === 'hong')>Hỏng</option>
          </select>
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('nhap-kho.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 nhap-kho-table">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th class="col-date">Ngày nhập</th>
            <th class="col-code">Mã đơn</th>
            <th class="col-code">Mã KH</th>
            <th class="col-product">Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end col-number">SL đặt</th>
            <th class="col-unit">Đơn vị may</th>
            <th class="text-end col-number">SL đạt</th>
            <th class="text-end col-number">SL lỗi</th>
            <th class="text-end col-number">SL hỏng</th>
            <th class="text-end col-number">Tổng nhập</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($nhapKhos as $nhapKho)
            @php
              $rowLoaiTon = $nhapKho->loai_ton ?? 'dat';
            @endphp
            <tr>
              <td>{{ $nhapKhos->firstItem() + $loop->index }}</td>
              <td class="col-date">{{ $nhapKho->ngay_nhap ? \Illuminate\Support\Carbon::parse($nhapKho->ngay_nhap)->format('d/m/Y') : '-' }}</td>
              <td class="col-code">{{ $nhapKho->source_has_order ? ($nhapKho->source_order_number ?? $nhapKho->donHangChiTiet?->donHang?->ma_don ?? '-') : '-' }}</td>
              <td class="col-code">{{ $nhapKho->source_has_order ? ($nhapKho->source_customer_number ?? $nhapKho->donHangChiTiet?->donHang?->ma_kh ?? '-') : '-' }}</td>
              <td class="col-product">
                <strong>{{ $nhapKho->source_product_code ?? $nhapKho->qc?->phanBoMay?->cat?->matHang?->ma_hang ?? $nhapKho->qc?->matHang?->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $nhapKho->source_product_name ?? $nhapKho->qc?->phanBoMay?->cat?->matHang?->ten_hang ?? $nhapKho->qc?->matHang?->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $nhapKho->source_color ?? $nhapKho->qc?->phanBoMay?->cat?->mau?->ten_mau ?? $nhapKho->qc?->mau?->ten_mau ?? '-' }}</td>
              <td>{{ $nhapKho->source_size ?? $nhapKho->qc?->phanBoMay?->cat?->size?->ten_size ?? $nhapKho->qc?->size?->ten_size ?? '-' }}</td>
              <td class="text-end col-number">{{ $nhapKho->source_has_order ? $formatPhanBoNumber($nhapKho->source_order_quantity ?? $nhapKho->donHangChiTiet?->so_luong_dat) : '-' }}</td>
              <td class="col-unit">{{ $nhapKho->source_unit_name ?? $nhapKho->qc?->phanBoMay?->donViMay?->ten_don_vi ?? '-' }}</td>
              <td class="text-end col-number">
                @if ($rowLoaiTon === 'dat')
                  <span class="badge bg-label-success">{{ $formatPhanBoNumber($nhapKho->so_luong_nhap) }}</span>
                @else
                  -
                @endif
              </td>
              <td class="text-end col-number">
                @if ($rowLoaiTon === 'loi')
                  <span class="badge bg-label-warning">{{ $formatPhanBoNumber($nhapKho->so_luong_nhap) }}</span>
                @else
                  -
                @endif
              </td>
              <td class="text-end col-number">
                @if ($rowLoaiTon === 'hong')
                  <span class="badge bg-label-danger">{{ $formatPhanBoNumber($nhapKho->so_luong_nhap) }}</span>
                @else
                  -
                @endif
              </td>
              <td class="text-end col-number fw-semibold">{{ $formatPhanBoNumber($nhapKho->so_luong_nhap) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="13" class="text-center py-4">Chưa có dữ liệu nhập kho.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($nhapKhos->hasPages())
      <div class="card-footer">
        {{ $nhapKhos->links() }}
      </div>
    @endif
  </div>
@endsection
