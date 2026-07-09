@extends('layouts/contentNavbarLayout')
@section('title', 'Nhập hàng')
@section('page-style')
@include('content.san-xuat._filter-style')
<style>
  .online-imports-scroll {
    overflow-x: auto;
    max-width: 100%;
    scrollbar-gutter: stable;
  }
  .online-imports-table {
    min-width: 1180px;
    margin-bottom: 0;
  }
  .online-imports-table thead th {
    background: var(--bs-gray-100);
    color: var(--bs-heading-color);
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .01em;
    text-transform: uppercase;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 3;
  }
  .online-imports-table th,
  .online-imports-table td {
    padding-top: .9rem;
    padding-bottom: .9rem;
  }
  .online-imports-table th {
    vertical-align: middle;
  }
  .online-imports-table td {
    vertical-align: top;
  }
  .online-imports-table tbody tr:hover {
    background: rgba(var(--bs-primary-rgb), .035);
  }
  .online-import-row-index {
    width: 64px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    white-space: nowrap;
  }
  .online-import-date {
    width: 112px;
    white-space: nowrap;
  }
  .online-import-product {
    min-width: 240px;
    max-width: 340px;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
    line-height: 1.45;
  }
  .online-import-value {
    min-width: 96px;
    white-space: nowrap;
  }
  .online-import-number {
    min-width: 96px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }
  .online-import-actions {
    min-width: 92px;
    white-space: nowrap;
  }
  .online-import-start td {
    border-top-width: 2px;
  }
  .online-product-start {
    border-top-width: 1px;
  }
</style>
@endsection
@section('content')
@include('content.danh-muc._toast')
@include('content.don-hang-online._sticky-table-header')
<div class="card">
  <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
    <h5 class="mb-0">Danh sách nhập hàng</h5>
    <div class="d-flex gap-2">
      @if (hasPermission('DON_HANG_HOAN_THANH_DELETE'))
        <button type="button" class="btn btn-outline-danger js-bulk-toggle"><i class="icon-base bx bx-select-multiple me-1"></i>Xóa hàng loạt</button>
      @endif
      @if (hasPermission('DON_HANG_HOAN_THANH_CREATE'))
        <a href="{{ route('nhap-hang-online.create') }}" class="btn btn-primary"><i class="icon-base bx bx-plus me-1"></i>Thêm nhập hàng</a>
      @endif
    </div>
  </div>
  @if (hasPermission('DON_HANG_HOAN_THANH_DELETE'))
    @include('content.san-xuat._bulk-delete', ['bulkRoute' => 'nhap-hang-online.bulk-destroy', 'bulkLabel' => 'phiếu nhập hàng'])
  @endif
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Sản phẩm</label>
        <input class="form-control" name="ten_san_pham" value="{{ $filters['ten_san_pham'] }}" list="nhap-hang-products" placeholder="Gõ hoặc chọn sản phẩm">
        <datalist id="nhap-hang-products">@foreach($filterOptions['products'] as $product)<option value="{{ $product }}"></option>@endforeach</datalist>
      </div>
      <div class="col-md-2"><label class="form-label">Màu</label><select class="form-select" name="mau"><option value="">Tất cả</option>@foreach($filterOptions['colors'] as $color)<option value="{{ $color }}" @selected($filters['mau'] === $color)>{{ $color }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Size</label><select class="form-select" name="size"><option value="">Tất cả</option>@foreach($filterOptions['sizes'] as $size)<option value="{{ $size }}" @selected($filters['size'] === $size)>{{ $size }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" class="form-control" name="tu_ngay" value="{{ $filters['tu_ngay'] }}"></div>
      <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" class="form-control" name="den_ngay" value="{{ $filters['den_ngay'] }}"></div>
      <div class="col-md-1">@include('content.shared._per-page-select', ['perPageColumnClass' => ''])</div>
      <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('nhap-hang-online.index') }}" class="btn btn-outline-secondary">Mới</a></div>
    </form>
  </div>
  <div class="online-imports-scroll js-sticky-table-wrap">
    <table class="table online-imports-table js-sticky-table">
      <thead><tr>@if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<th class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-check-all" type="checkbox" aria-label="Chọn tất cả"></th>@endif<th class="online-import-row-index">STT</th><th class="online-import-date">Ngày nhập</th><th>Tên sản phẩm</th><th>Màu</th><th>Size</th><th class="text-end online-import-number">SL</th><th class="text-end online-import-number">Đơn giá</th><th class="text-end online-import-number">Thành tiền</th><th class="text-end online-import-number">Tổng SL</th><th class="text-end online-import-number">Tổng tiền ngày nhập</th><th class="online-import-actions">Thao tác</th></tr></thead>
      <tbody>
      @forelse($imports as $import)
        @php
          $groupedDetails = $import->chiTiets->groupBy('ten_san_pham');
          $importNumber = $imports->firstItem() + $loop->index;
        @endphp
        @forelse($groupedDetails as $product => $details)
          @php
            $productRowspan = max(1, $details->count());
            $productQuantity = $details->sum(fn ($detail) => (float) $detail->so_luong);
          @endphp
          @foreach($details as $detail)
              @if($loop->first)
                <tr class="online-import-start">
                @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none" rowspan="{{ $productRowspan }}"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $import->id }}" aria-label="Chọn phiếu nhập {{ $importNumber }}"></td>@endif
                <td class="online-import-row-index" rowspan="{{ $productRowspan }}">{{ $importNumber }}</td>
                <td class="online-import-date" rowspan="{{ $productRowspan }}">{{ $import->ngay_nhap->format('d/m/Y') }}</td>
                <td rowspan="{{ $productRowspan }}"><strong class="online-import-product" title="{{ $product }}">{{ $product }}</strong></td>
              @else
                <tr>
              @endif
              <td class="online-import-value">{{ $detail->mau ?: '-' }}</td>
              <td class="online-import-value">{{ $detail->size ?: '-' }}</td>
              <td class="text-end online-import-number">{{ number_format((float) $detail->so_luong, 0, ',', '.') }}</td>
              <td class="text-end online-import-number">{{ number_format((float) $detail->don_gia, 0, ',', '.') }} ₫</td>
              <td class="text-end online-import-number">{{ number_format((float) $detail->thanh_tien, 0, ',', '.') }} ₫</td>
              @if($loop->first)
                <td class="text-end fw-semibold online-import-number" rowspan="{{ $productRowspan }}">{{ number_format((float) $productQuantity, 0, ',', '.') }}</td>
                <td class="text-end fw-semibold online-import-number" rowspan="{{ $productRowspan }}">{{ number_format((float) ($dailyTotals[$import->ngay_nhap->toDateString()] ?? $import->tong_thanh_tien), 0, ',', '.') }} ₫</td>
                <td class="online-import-actions" rowspan="{{ $productRowspan }}"><div class="d-flex gap-2">
                  @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('nhap-hang-online.edit', $import) }}"><i class="icon-base bx bx-edit"></i></a>@endif
                  @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('nhap-hang-online.destroy', $import) }}" onsubmit="return confirm('Bạn có chắc muốn xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
                </div></td>
              @endif
            </tr>
          @endforeach
        @empty
          <tr class="online-import-start">
            @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $import->id }}" aria-label="Chọn phiếu nhập {{ $importNumber }}"></td>@endif
            <td class="online-import-row-index">{{ $importNumber }}</td>
            <td class="online-import-date">{{ $import->ngay_nhap->format('d/m/Y') }}</td>
            <td><strong class="online-import-product">-</strong></td>
            <td class="online-import-value">-</td><td class="online-import-value">-</td><td class="text-end online-import-number">0</td><td class="text-end online-import-number">0 ₫</td><td class="text-end online-import-number">0 ₫</td>
            <td class="text-end fw-semibold online-import-number">{{ number_format((float) $import->tong_so_luong, 0, ',', '.') }}</td>
            <td class="text-end fw-semibold online-import-number">{{ number_format((float) ($dailyTotals[$import->ngay_nhap->toDateString()] ?? $import->tong_thanh_tien), 0, ',', '.') }} ₫</td>
            <td class="online-import-actions"><div class="d-flex gap-2">
              @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('nhap-hang-online.edit', $import) }}"><i class="icon-base bx bx-edit"></i></a>@endif
              @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('nhap-hang-online.destroy', $import) }}" onsubmit="return confirm('Bạn có chắc muốn xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
            </div></td>
          </tr>
        @endforelse
      @empty
        <tr><td colspan="{{ hasPermission('DON_HANG_HOAN_THANH_DELETE') ? 12 : 11 }}" class="text-center py-4">Chưa có dữ liệu.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($imports->hasPages())<div class="card-footer">{{ $imports->links() }}</div>@endif
</div>
@endsection
