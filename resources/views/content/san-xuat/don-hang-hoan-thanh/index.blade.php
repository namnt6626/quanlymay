@extends('layouts/contentNavbarLayout')
@section('title', 'Đơn hàng hoàn thành')
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
  }
  .completed-orders-table th,
  .completed-orders-table td {
    padding-top: .9rem;
    padding-bottom: .9rem;
    vertical-align: middle;
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
  .completed-variant-list {
    min-width: 320px;
    max-width: 460px;
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
  }
  .completed-variant-chip {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    overflow: hidden;
    border: 1px solid rgba(var(--bs-primary-rgb), .18);
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), .08);
    color: var(--bs-body-color);
    font-size: .78rem;
    font-weight: 600;
    line-height: 1.2;
  }
  .completed-variant-chip span {
    padding: .32rem .5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .completed-variant-chip span + span {
    border-left: 1px solid rgba(var(--bs-primary-rgb), .16);
  }
  .completed-variant-chip .completed-variant-qty {
    background: rgba(var(--bs-primary-rgb), .12);
    color: var(--bs-primary);
    font-variant-numeric: tabular-nums;
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
</style>
@endsection
@section('content')
@include('content.danh-muc._toast')
<div class="card">
  <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
    <h5 class="mb-0">Danh sách đơn hàng hoàn thành</h5>
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
    @include('content.san-xuat._bulk-delete', ['bulkRoute' => 'don-hang-hoan-thanh.bulk-destroy', 'bulkLabel' => 'đơn hàng hoàn thành'])
  @endif
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4"><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Tên sản phẩm, màu, size"></div>
      <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" class="form-control" name="tu_ngay" value="{{ $filters['tu_ngay'] }}"></div>
      <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" class="form-control" name="den_ngay" value="{{ $filters['den_ngay'] }}"></div>
      <div class="col-md-2"><label class="form-label">Kênh bán</label><select class="form-select" name="kenh_ban"><option value="">Tất cả</option><option value="Tiktok" @selected($filters['kenh_ban']==='Tiktok')>Tiktok</option><option value="Shopee" @selected($filters['kenh_ban']==='Shopee')>Shopee</option></select></div>
      <div class="col-md-2"><label class="form-label">Nguồn</label><select class="form-select" name="nguon"><option value="">Tất cả</option><option value="excel" @selected($filters['nguon']==='excel')>Excel</option><option value="thu_cong" @selected($filters['nguon']==='thu_cong')>Thủ công</option></select></div>
      <div class="col-md-2">@include('content.shared._per-page-select', ['perPageColumnClass' => ''])</div>
      <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('don-hang-hoan-thanh.index') }}" class="btn btn-outline-secondary">Mới</a></div>
    </form>
  </div>
  <div class="completed-orders-scroll">
    <table class="table align-middle completed-orders-table">
      <thead><tr>@if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<th class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-check-all" type="checkbox" aria-label="Chọn tất cả"></th>@endif<th class="completed-row-index">STT</th><th class="completed-date">Ngày</th><th>Tên sản phẩm</th><th>Màu x Size x SL</th><th class="text-end completed-number">Tổng SL</th><th class="text-end completed-number">Tổng tiền</th><th class="completed-channel">Kênh bán</th><th class="completed-source">Nguồn</th><th class="completed-actions">Thao tác</th></tr></thead>
      <tbody>
      @forelse($orders as $order)
        <tr>
          @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $order->id }}" aria-label="Chọn {{ $order->ten_san_pham }}"></td>@endif
          <td class="completed-row-index">{{ $orders->firstItem() + $loop->index }}</td><td class="completed-date">{{ $order->ngay_hoan_thanh->format('d/m/Y') }}</td>
          <td><strong class="completed-product-name" title="{{ $order->ten_san_pham }}">{{ $order->ten_san_pham }}</strong></td>
          <td class="completed-variant-list">
            @foreach($order->chiTiets as $detail)
              <span class="completed-variant-chip" title="{{ $detail->mau ?: '-' }} x {{ $detail->size ?: '-' }} x {{ number_format((float) $detail->so_luong, 0, ',', '.') }}">
                <span>{{ $detail->mau ?: '-' }}</span>
                <span>{{ $detail->size ?: '-' }}</span>
                <span class="completed-variant-qty">{{ number_format((float) $detail->so_luong, 0, ',', '.') }}</span>
              </span>
            @endforeach
          </td>
          <td class="text-end fw-semibold completed-number">{{ number_format((float)$order->tong_so_luong, 0, ',', '.') }}</td>
          <td class="text-end fw-semibold completed-number">{{ number_format((float)$order->tong_thanh_tien, 0, ',', '.') }} ₫</td>
          <td class="completed-channel"><span class="badge {{ $order->kenh_ban === 'Shopee' ? 'bg-label-warning' : 'bg-label-info' }}">{{ in_array($order->kenh_ban, ['Tiktok', 'Shopee'], true) ? $order->kenh_ban : '-' }}</span></td>
          <td class="completed-source">@foreach($order->chiTiets->pluck('nguon')->unique() as $source)<span class="badge {{ $source === 'excel' ? 'bg-label-success' : 'bg-label-primary' }}">{{ $source === 'excel' ? 'Excel' : 'Thủ công' }}</span>@endforeach</td>
          <td class="completed-actions"><div class="d-flex gap-2">
            @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('don-hang-hoan-thanh.edit', $order) }}"><i class="icon-base bx bx-edit"></i></a>@endif
            @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('don-hang-hoan-thanh.destroy', $order) }}" onsubmit="return confirm('Bạn có chắc muốn xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
          </div></td>
        </tr>
      @empty<tr><td colspan="{{ hasPermission('DON_HANG_HOAN_THANH_DELETE') ? 10 : 9 }}" class="text-center py-4">Chưa có dữ liệu.</td></tr>@endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
</div>
@endsection
