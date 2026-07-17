@extends('layouts/contentNavbarLayout')

@section('title', 'Xuất kho')

@section('page-style')
  @include('content.san-xuat._filter-style')
  <style>
    .xuat-kho-filter .input-group-text {
      background-color: var(--bs-gray-100);
      border-color: var(--bs-border-color);
      color: var(--bs-secondary-color);
      min-width: 42px;
      padding-left: .7rem;
      padding-right: .7rem;
    }

    .xuat-kho-filter .form-control,
    .xuat-kho-filter .form-select {
      font-weight: 500;
    }

    .xuat-kho-filter .filter-actions {
      align-items: end;
    }

    @media (min-width: 992px) {
      .xuat-kho-filter.production-filter-grid .filter-actions {
        grid-template-columns: minmax(0, 1fr) minmax(0, .95fr);
      }
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
      <h5 class="mb-0">Danh sách xuất kho</h5>
      <div class="d-flex gap-2 flex-wrap">
        @if (hasPermission('XUAT_KHO_DELETE'))
          <button type="button" class="btn btn-outline-danger js-bulk-toggle">
            <i class="icon-base bx bx-select-multiple me-1"></i> Xóa hàng loạt
          </button>
        @endif
        @if (hasPermission('XUAT_KHO_CREATE'))
          <a href="{{ route('xuat-kho.create') }}" class="btn btn-primary">
            <i class="icon-base bx bx-plus me-1"></i> Thêm mới
          </a>
        @endif
      </div>
    </div>

    @if (hasPermission('XUAT_KHO_DELETE'))
      @include('content.san-xuat._bulk-delete', ['bulkRoute' => 'xuat-kho.bulk-destroy', 'bulkLabel' => 'phiếu xuất kho'])
    @endif

    <div class="card-body">
      <form action="{{ route('xuat-kho.index') }}" method="GET" class="row production-filter-form production-filter-grid xuat-kho-filter align-items-end">
        <div class="col-12 col-lg-4 filter-span-4">
          <label class="form-label" for="ma_hang">Mã hàng</label>
          <div class="input-group">
            <span class="input-group-text"><i class="icon-base bx bx-barcode"></i></span>
            <input type="search" class="form-control" id="ma_hang" name="ma_hang" value="{{ $filters['ma_hang'] }}"
              list="ma_hang_options" placeholder="Gõ hoặc chọn mã hàng" autocomplete="off">
          </div>
          <datalist id="ma_hang_options">
            @foreach ($matHangs as $matHang)
              <option value="{{ $matHang->ma_hang }}" label="{{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}"></option>
            @endforeach
          </datalist>
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="ma_mau">Màu</label>
          <div class="input-group">
            <span class="input-group-text"><i class="icon-base bx bx-palette"></i></span>
            <select class="form-select" id="ma_mau" name="ma_mau">
              <option value="">Tất cả</option>
              @foreach ($maus as $mau)
                <option value="{{ $mau->ma_mau }}" @selected($filters['ma_mau'] === $mau->ma_mau)>{{ $mau->ten_mau }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="ma_size">Size</label>
          <div class="input-group">
            <span class="input-group-text"><i class="icon-base bx bx-ruler"></i></span>
            <select class="form-select" id="ma_size" name="ma_size">
              <option value="">Tất cả</option>
              @foreach ($sizes as $size)
                <option value="{{ $size->ma_size }}" @selected($filters['ma_size'] === $size->ma_size)>{{ $size->ten_size }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="tu_ngay">Từ ngày</label>
          <div class="input-group">
            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
            <input type="date" class="form-control" id="tu_ngay" name="tu_ngay" value="{{ $filters['tu_ngay'] }}">
          </div>
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="den_ngay">Đến ngày</label>
          <div class="input-group">
            <span class="input-group-text"><i class="icon-base bx bx-calendar-check"></i></span>
            <input type="date" class="form-control" id="den_ngay" name="den_ngay" value="{{ $filters['den_ngay'] }}">
          </div>
        </div>
        <div class="col-12 col-lg-3 filter-span-3">
          <label class="form-label" for="kenh_ban">Kênh bán</label>
          <div class="input-group">
            <span class="input-group-text"><i class="icon-base bx bx-store"></i></span>
            <input type="text" class="form-control" id="kenh_ban" name="kenh_ban" value="{{ $filters['kenh_ban'] }}"
              placeholder="Nhập kênh bán">
          </div>
        </div>
        @include('content.shared._per-page-select', ['perPageColumnClass' => 'col-6 col-lg-2 filter-span-2'])

        <div class="col-12 col-lg-3 filter-span-3">
          <div class="d-flex gap-2 flex-wrap filter-actions">
            <button type="submit" class="btn btn-primary flex-fill flex-sm-grow-0">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('xuat-kho.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
              <i class="icon-base bx bx-refresh me-1"></i> Làm mới
            </a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            @if (hasPermission('XUAT_KHO_DELETE'))
              <th class="bulk-select-cell js-bulk-column d-none">
                <input class="form-check-input js-bulk-check-all" type="checkbox" aria-label="Chọn tất cả phiếu xuất kho">
              </th>
            @endif
            <th style="width: 80px;">STT</th>
            <th>Số phiếu</th>
            <th>Ngày xuất</th>
            <th>Ngày nhập/QC</th>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Kênh bán</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">SL đặt</th>
            <th class="text-end">Nhập kho</th>
            <th class="text-end">SL xuất</th>
            <th class="text-end">Đơn giá</th>
            <th class="text-end">Thành tiền</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($chiTiets as $chiTiet)
            <tr>
              @if (hasPermission('XUAT_KHO_DELETE'))
                <td class="bulk-select-cell js-bulk-column d-none">
                  <input class="form-check-input js-bulk-item" type="checkbox"
                    value="{{ $chiTiet->phieu_xuat_kho_id }}" aria-label="Chọn phiếu xuất {{ $chiTiet->phieu_xuat_kho_id }}">
                </td>
              @endif
              <td>{{ $chiTiets->firstItem() + $loop->index }}</td>
              <td>{{ $chiTiet->phieuXuatKho?->so_phieu ?? '-' }}</td>
              <td>{{ $chiTiet->phieuXuatKho?->ngay_xuat ? \Illuminate\Support\Carbon::parse($chiTiet->phieuXuatKho->ngay_xuat)->format('d/m/Y') : '-' }}</td>
              <td>
                {{ $chiTiet->nhapKho?->ngay_nhap
                    ? \Illuminate\Support\Carbon::parse($chiTiet->nhapKho->ngay_nhap)->format('d/m/Y')
                    : ($chiTiet->nhapKho?->qc?->ngay_qc ? \Illuminate\Support\Carbon::parse($chiTiet->nhapKho->qc->ngay_qc)->format('d/m/Y') : '-') }}
              </td>
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
              <td class="text-end">{{ number_format((float) $chiTiet->don_gia, 0, ',', '.') }} ₫</td>
              <td class="text-end fw-semibold">{{ number_format((float) $chiTiet->thanh_tien, 0, ',', '.') }} ₫</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('XUAT_KHO_EDIT'))
                    <a href="{{ route('xuat-kho.edit', $chiTiet->phieuXuatKho) }}"
                      class="btn btn-sm btn-icon btn-outline-primary" title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('XUAT_KHO_DELETE'))
                    <form action="{{ route('xuat-kho.destroy', ['phieu_xuat_kho' => $chiTiet->phieuXuatKho] + request()->query()) }}" method="POST"
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
              <td colspan="{{ hasPermission('XUAT_KHO_DELETE') ? 17 : 16 }}" class="text-center py-4">Chưa có dữ liệu xuất kho.</td>
            </tr>
          @endforelse
        </tbody>
        @if ($chiTiets->isNotEmpty())
          <tfoot>
            <tr class="fw-semibold">
              <td colspan="{{ hasPermission('XUAT_KHO_DELETE') ? 13 : 12 }}" class="text-end">Tổng trang</td>
              <td class="text-end">{{ $formatPhanBoNumber($totals['so_luong_xuat'] ?? 0) }}</td>
              <td></td>
              <td class="text-end">{{ number_format((float) ($totals['thanh_tien'] ?? 0), 0, ',', '.') }} ₫</td>
              <td></td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    @if ($chiTiets->hasPages())
      <div class="card-footer">
        {{ $chiTiets->links() }}
      </div>
    @endif
  </div>
@endsection
