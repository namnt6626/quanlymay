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
    min-width: 1450px;
  }
  .completed-orders-table th,
  .completed-orders-table td {
    vertical-align: top;
  }
  .completed-product-name {
    min-width: 310px;
    max-width: 420px;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    overflow: hidden;
    line-height: 1.45;
  }
  .completed-variant-list {
    min-width: 150px;
  }
  .completed-variant-item {
    min-height: 27px;
    margin-bottom: .25rem;
    display: flex;
    align-items: center;
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
      <div class="col-md-4"><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="Tên sản phẩm, kho, màu, size"></div>
      <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" class="form-control" name="tu_ngay" value="{{ $filters['tu_ngay'] }}"></div>
      <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" class="form-control" name="den_ngay" value="{{ $filters['den_ngay'] }}"></div>
      <div class="col-md-2"><label class="form-label">Kho</label><select class="form-select" name="ten_kho"><option value="">Tất cả</option>@foreach($warehouses as $warehouse)<option @selected($filters['ten_kho'] === $warehouse)>{{ $warehouse }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Nguồn</label><select class="form-select" name="nguon"><option value="">Tất cả</option><option value="excel" @selected($filters['nguon']==='excel')>Excel</option><option value="thu_cong" @selected($filters['nguon']==='thu_cong')>Thủ công</option></select></div>
      <div class="col-md-2">@include('content.shared._per-page-select', ['perPageColumnClass' => ''])</div>
      <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('don-hang-hoan-thanh.index') }}" class="btn btn-outline-secondary">Mới</a></div>
    </form>
  </div>
  <div class="completed-orders-scroll">
    <table class="table align-middle completed-orders-table">
      <thead><tr>@if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<th class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-check-all" type="checkbox" aria-label="Chọn tất cả"></th>@endif<th>STT</th><th>Ngày</th><th>Tên sản phẩm</th><th>Kho</th><th>Màu</th><th>Size</th><th class="text-end">Tổng SL</th><th class="text-end">Tổng tiền</th><th>Nguồn</th><th>Thao tác</th></tr></thead>
      <tbody>
      @forelse($orders as $order)
        <tr>
          @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $order->id }}" aria-label="Chọn {{ $order->ten_san_pham }}"></td>@endif
          <td>{{ $orders->firstItem() + $loop->index }}</td><td>{{ $order->ngay_hoan_thanh->format('d/m/Y') }}</td>
          <td><strong class="completed-product-name" title="{{ $order->ten_san_pham }}">{{ $order->ten_san_pham }}</strong></td><td>{{ $order->ten_kho ?: '-' }}</td>
          <td class="completed-variant-list">
            @foreach($order->chiTiets as $detail)
              <div class="completed-variant-item"><span class="badge bg-label-secondary">{{ $detail->mau ?: '-' }}</span></div>
            @endforeach
          </td>
          <td class="completed-variant-list">
            @foreach($order->chiTiets as $detail)
              <div class="completed-variant-item"><span class="badge bg-label-info">{{ $detail->size ?: '-' }}</span></div>
            @endforeach
          </td>
          <td class="text-end fw-semibold">{{ number_format((float)$order->tong_so_luong, 0, ',', '.') }}</td>
          <td class="text-end fw-semibold">{{ number_format((float)$order->tong_thanh_tien, 0, ',', '.') }} ₫</td>
          <td>@foreach($order->chiTiets->pluck('nguon')->unique() as $source)<span class="badge {{ $source === 'excel' ? 'bg-label-success' : 'bg-label-primary' }}">{{ $source === 'excel' ? 'Excel' : 'Thủ công' }}</span>@endforeach</td>
          <td><div class="d-flex gap-2">
            @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('don-hang-hoan-thanh.edit', $order) }}"><i class="icon-base bx bx-edit"></i></a>@endif
            @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('don-hang-hoan-thanh.destroy', $order) }}" onsubmit="return confirm('Bạn có chắc muốn xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
          </div></td>
        </tr>
      @empty<tr><td colspan="{{ hasPermission('DON_HANG_HOAN_THANH_DELETE') ? 11 : 10 }}" class="text-center py-4">Chưa có dữ liệu.</td></tr>@endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
</div>
@endsection
