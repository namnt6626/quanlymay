@extends('layouts/contentNavbarLayout')
@section('title', 'Xuất hàng')
@section('page-style')
@include('content.san-xuat._filter-style')
<style>
  .completed-orders-scroll {
    overflow-x: auto;
    max-width: 100%;
    scrollbar-gutter: stable;
  }
  .completed-orders-table {
    min-width: 1180px;
    margin-bottom: 0;
  }
  .completed-orders-table thead th {
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
  .completed-orders-table th,
  .completed-orders-table td {
    padding-top: .9rem;
    padding-bottom: .9rem;
  }
  .completed-orders-table th {
    vertical-align: middle;
  }
  .completed-orders-table td {
    vertical-align: top;
  }
  .completed-orders-table tbody tr:hover {
    background: rgba(var(--bs-primary-rgb), .035);
  }
  .completed-row-index {
    width: 64px;
    color: var(--bs-secondary-color);
    font-weight: 600;
    white-space: nowrap;
  }
  .completed-date {
    width: 112px;
    white-space: nowrap;
  }
  .completed-product-name {
    min-width: 240px;
    max-width: 340px;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
    line-height: 1.45;
  }
  .completed-variant-value {
    min-width: 96px;
    white-space: nowrap;
  }
  .completed-number {
    min-width: 96px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }
  .completed-source {
    min-width: 88px;
    white-space: nowrap;
  }
  .completed-channel {
    min-width: 96px;
    white-space: nowrap;
  }
  .completed-actions {
    min-width: 92px;
    white-space: nowrap;
  }
  .completed-order-start td {
    border-top-width: 2px;
  }
</style>
@endsection
@section('content')
@include('content.danh-muc._toast')
@include('content.don-hang-online._sticky-table-header')
<div class="card">
  <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
    <h5 class="mb-0">Danh sách xuất hàng</h5>
      <div class="d-flex gap-2">
        @if (hasPermission('DON_HANG_HOAN_THANH_DELETE'))
          <button type="button" class="btn btn-outline-danger js-bulk-toggle"><i class="icon-base bx bx-select-multiple me-1"></i>Xóa hàng loạt</button>
        @endif
        @if (hasPermission('DON_HANG_HOAN_THANH_CREATE'))
        <a href="{{ route('don-hang-hoan-thanh.import') }}" class="btn btn-outline-success"><i class="icon-base bx bx-upload me-1"></i>Import Excel</a>
        <a href="{{ route('don-hang-hoan-thanh.create') }}" class="btn btn-primary"><i class="icon-base bx bx-plus me-1"></i>Nhập thủ công</a>
        @endif
      </div>
  </div>
  @if (hasPermission('DON_HANG_HOAN_THANH_DELETE'))
    @include('content.san-xuat._bulk-delete', ['bulkRoute' => 'don-hang-hoan-thanh.bulk-destroy', 'bulkLabel' => 'xuất hàng'])
  @endif
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-12 col-lg-4 col-xl-4">
        <label class="form-label">Sản phẩm</label>
        <input class="form-control" name="ten_san_pham" value="{{ $filters['ten_san_pham'] }}" list="xuat-hang-products" placeholder="Gõ hoặc chọn sản phẩm">
        <datalist id="xuat-hang-products">@foreach($filterOptions['products'] as $product)<option value="{{ $product }}"></option>@endforeach</datalist>
      </div>
      <div class="col-6 col-lg-2 col-xl-2"><label class="form-label">Màu</label><select class="form-select" name="mau"><option value="">Tất cả</option>@foreach($filterOptions['colors'] as $color)<option value="{{ $color }}" @selected($filters['mau'] === $color)>{{ $color }}</option>@endforeach</select></div>
      <div class="col-6 col-lg-2 col-xl-2"><label class="form-label">Size</label><select class="form-select" name="size"><option value="">Tất cả</option>@foreach($filterOptions['sizes'] as $size)<option value="{{ $size }}" @selected($filters['size'] === $size)>{{ $size }}</option>@endforeach</select></div>
      <div class="col-6 col-lg-2 col-xl-2"><label class="form-label">Từ ngày</label><input type="date" class="form-control" name="tu_ngay" value="{{ $filters['tu_ngay'] }}"></div>
      <div class="col-6 col-lg-2 col-xl-2"><label class="form-label">Đến ngày</label><input type="date" class="form-control" name="den_ngay" value="{{ $filters['den_ngay'] }}"></div>
      <div class="col-6 col-lg-3 col-xl-3"><label class="form-label">Kênh bán</label><select class="form-select" name="kenh_ban"><option value="">Tất cả</option><option value="Tiktok" @selected($filters['kenh_ban']==='Tiktok')>Tiktok</option><option value="Shopee" @selected($filters['kenh_ban']==='Shopee')>Shopee</option></select></div>
      <div class="col-6 col-lg-3 col-xl-3"><label class="form-label">Nguồn</label><select class="form-select" name="nguon"><option value="">Tất cả</option><option value="excel" @selected($filters['nguon']==='excel')>Excel</option><option value="thu_cong" @selected($filters['nguon']==='thu_cong')>Thủ công</option></select></div>
      <div class="col-6 col-lg-2 col-xl-2">@include('content.shared._per-page-select', ['perPageColumnClass' => ''])</div>
      <div class="col-12 col-lg-4 col-xl-4 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('don-hang-hoan-thanh.index') }}" class="btn btn-outline-secondary flex-fill">Mới</a></div>
    </form>
  </div>
  <div class="completed-orders-scroll js-sticky-table-wrap">
    <table class="table completed-orders-table js-sticky-table">
      <thead><tr>@if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<th class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-check-all" type="checkbox" aria-label="Chọn tất cả"></th>@endif<th class="completed-row-index">STT</th><th class="completed-date">Ngày</th><th>Tên sản phẩm</th><th>Màu</th><th>Size</th><th class="text-end completed-number">SL</th><th class="text-end completed-number">Thành tiền</th><th class="text-end completed-number">Tổng SL</th><th class="text-end completed-number">Tổng tiền</th><th class="completed-channel">Kênh bán</th><th class="completed-source">Nguồn</th><th class="completed-actions">Thao tác</th></tr></thead>
      <tbody>
      @forelse($orders as $order)
        @php
          $rowspan = max(1, $order->chiTiets->count());
          $orderNumber = $orders->firstItem() + $loop->index;
        @endphp
        @forelse($order->chiTiets as $detail)
          <tr @class(['completed-order-start' => $loop->first])>
            @if($loop->first)
              @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none" rowspan="{{ $rowspan }}"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $order->id }}" aria-label="Chọn {{ $order->ten_san_pham }}"></td>@endif
              <td class="completed-row-index" rowspan="{{ $rowspan }}">{{ $orderNumber }}</td>
              <td class="completed-date" rowspan="{{ $rowspan }}">{{ $order->ngay_hoan_thanh->format('d/m/Y') }}</td>
              <td rowspan="{{ $rowspan }}"><strong class="completed-product-name" title="{{ $order->ten_san_pham }}">{{ $order->ten_san_pham }}</strong></td>
            @endif
            <td class="completed-variant-value">{{ $detail->mau ?: '-' }}</td>
            <td class="completed-variant-value">{{ $detail->size ?: '-' }}</td>
            <td class="text-end completed-number">{{ number_format((float) $detail->so_luong, 0, ',', '.') }}</td>
            <td class="text-end completed-number">{{ number_format((float) $detail->thanh_tien, 0, ',', '.') }} ₫</td>
            @if($loop->first)
              <td class="text-end fw-semibold completed-number" rowspan="{{ $rowspan }}">{{ number_format((float)$order->tong_so_luong, 0, ',', '.') }}</td>
              <td class="text-end fw-semibold completed-number" rowspan="{{ $rowspan }}">{{ number_format((float)$order->tong_thanh_tien, 0, ',', '.') }} ₫</td>
              <td class="completed-channel" rowspan="{{ $rowspan }}"><span class="badge {{ $order->kenh_ban === 'Shopee' ? 'bg-label-warning' : 'bg-label-info' }}">{{ in_array($order->kenh_ban, ['Tiktok', 'Shopee'], true) ? $order->kenh_ban : '-' }}</span></td>
              <td class="completed-source" rowspan="{{ $rowspan }}">@foreach($order->chiTiets->pluck('nguon')->unique() as $source)<span class="badge {{ $source === 'excel' ? 'bg-label-success' : 'bg-label-primary' }}">{{ $source === 'excel' ? 'Excel' : 'Thủ công' }}</span>@endforeach</td>
              <td class="completed-actions" rowspan="{{ $rowspan }}"><div class="d-flex gap-2">
                @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('don-hang-hoan-thanh.edit', $order) }}"><i class="icon-base bx bx-edit"></i></a>@endif
                @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('don-hang-hoan-thanh.destroy', $order) }}" onsubmit="return confirm('Bạn có chắc muốn xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
              </div></td>
            @endif
          </tr>
        @empty
          <tr class="completed-order-start">
            @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $order->id }}" aria-label="Chọn {{ $order->ten_san_pham }}"></td>@endif
            <td class="completed-row-index">{{ $orderNumber }}</td>
            <td class="completed-date">{{ $order->ngay_hoan_thanh->format('d/m/Y') }}</td>
            <td><strong class="completed-product-name" title="{{ $order->ten_san_pham }}">{{ $order->ten_san_pham }}</strong></td>
            <td class="completed-variant-value">-</td><td class="completed-variant-value">-</td><td class="text-end completed-number">0</td><td class="text-end completed-number">0 ₫</td>
            <td class="text-end fw-semibold completed-number">{{ number_format((float)$order->tong_so_luong, 0, ',', '.') }}</td>
            <td class="text-end fw-semibold completed-number">{{ number_format((float)$order->tong_thanh_tien, 0, ',', '.') }} ₫</td>
            <td class="completed-channel"><span class="badge {{ $order->kenh_ban === 'Shopee' ? 'bg-label-warning' : 'bg-label-info' }}">{{ in_array($order->kenh_ban, ['Tiktok', 'Shopee'], true) ? $order->kenh_ban : '-' }}</span></td>
            <td class="completed-source">-</td>
            <td class="completed-actions"><div class="d-flex gap-2">
              @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('don-hang-hoan-thanh.edit', $order) }}"><i class="icon-base bx bx-edit"></i></a>@endif
              @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('don-hang-hoan-thanh.destroy', $order) }}" onsubmit="return confirm('Bạn có chắc muốn xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
            </div></td>
          </tr>
        @endforelse
      @empty<tr><td colspan="{{ hasPermission('DON_HANG_HOAN_THANH_DELETE') ? 13 : 12 }}" class="text-center py-4">Chưa có dữ liệu.</td></tr>@endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
</div>
@endsection
