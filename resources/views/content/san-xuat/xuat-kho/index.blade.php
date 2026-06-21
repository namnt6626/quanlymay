@extends('layouts/contentNavbarLayout')

@section('title', 'Xuất kho')

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
      <h5 class="mb-0">Danh sách xuất kho</h5>
      @if (hasPermission('XUAT_KHO_CREATE'))
        <a href="{{ route('xuat-kho.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('xuat-kho.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn, mã KH, kênh bán, mã hàng, tên hàng, màu hoặc size">
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('xuat-kho.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Số phiếu</th>
            <th>Ngày xuất</th>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Kênh bán</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">SL đặt</th>
            <th class="text-end">Nhập kho</th>
            <th class="text-end">SL xuất</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($chiTiets as $chiTiet)
            <tr>
              <td>{{ $chiTiets->firstItem() + $loop->index }}</td>
              <td>{{ $chiTiet->phieuXuatKho?->so_phieu ?? '-' }}</td>
              <td>{{ $chiTiet->phieuXuatKho?->ngay_xuat ? \Illuminate\Support\Carbon::parse($chiTiet->phieuXuatKho->ngay_xuat)->format('d/m/Y') : '-' }}</td>
              <td>{{ $chiTiet->source_has_order ? ($chiTiet->source_order_number ?? $chiTiet->nhapKho?->donHangChiTiet?->donHang?->ma_don ?? '-') : '-' }}</td>
              <td>{{ $chiTiet->source_has_order ? ($chiTiet->source_customer_number ?? $chiTiet->nhapKho?->donHangChiTiet?->donHang?->ma_kh ?? '-') : '-' }}</td>
              <td>{{ $chiTiet->source_kenh_ban ?? $chiTiet->phieuXuatKho?->kenh_ban ?? '-' }}</td>
              <td>
                <strong>{{ $chiTiet->source_product_code ?? $chiTiet->nhapKho?->qc?->phanBoMay?->cat?->matHang?->ma_hang ?? $chiTiet->nhapKho?->qc?->matHang?->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $chiTiet->source_product_name ?? $chiTiet->nhapKho?->qc?->phanBoMay?->cat?->matHang?->ten_hang ?? $chiTiet->nhapKho?->qc?->matHang?->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $chiTiet->source_color ?? $chiTiet->nhapKho?->qc?->phanBoMay?->cat?->mau?->ten_mau ?? $chiTiet->nhapKho?->qc?->mau?->ten_mau ?? '-' }}</td>
              <td>{{ $chiTiet->source_size ?? $chiTiet->nhapKho?->qc?->phanBoMay?->cat?->size?->ten_size ?? $chiTiet->nhapKho?->qc?->size?->ten_size ?? '-' }}</td>
              <td class="text-end">{{ $chiTiet->source_has_order ? $formatPhanBoNumber($chiTiet->source_order_quantity ?? $chiTiet->donHangChiTiet?->so_luong_dat) : '-' }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($chiTiet->source_total_imported ?? $chiTiet->nhapKho?->so_luong_nhap) }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($chiTiet->so_luong_xuat) }}</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('XUAT_KHO_EDIT'))
                    <a href="{{ route('xuat-kho.edit', $chiTiet->phieuXuatKho) }}"
                      class="btn btn-sm btn-icon btn-outline-primary" title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('XUAT_KHO_DELETE'))
                    <form action="{{ route('xuat-kho.destroy', $chiTiet->phieuXuatKho) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa xuất kho này?');">
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
              <td colspan="13" class="text-center py-4">Chưa có dữ liệu xuất kho.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($chiTiets->hasPages())
      <div class="card-footer">
        {{ $chiTiets->links() }}
      </div>
    @endif
  </div>
@endsection
