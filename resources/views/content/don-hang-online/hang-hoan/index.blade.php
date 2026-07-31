@extends('layouts/contentNavbarLayout')
@section('title', 'Hàng hoàn')
@section('page-style')
@include('content.san-xuat._filter-style')
<style>
  .return-table-wrap { overflow-x: auto; scrollbar-gutter: stable; }
  .return-table { min-width: 1180px; }
  .return-table th { background: var(--bs-gray-100); font-size: .76rem; text-transform: uppercase; white-space: nowrap; }
  .return-number { white-space: nowrap; font-variant-numeric: tabular-nums; }
  .return-product { max-width: 320px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
@endsection
@section('content')
@include('content.danh-muc._toast')
@php
  $statusLabel = fn (?string $value): string => [
    'Completed' => 'Đã hoàn tất',
    'To Process' => 'Chờ xử lý',
    'In Process' => 'Đang xử lý',
    'Refund rejected' => 'Hoàn bị từ chối',
  ][$value ?? ''] ?? ($value ?: '-');
  $statusClass = fn (?string $value): string => [
    'Completed' => 'bg-label-success',
    'To Process' => 'bg-label-warning',
    'In Process' => 'bg-label-warning',
    'Refund rejected' => 'bg-label-danger',
  ][$value ?? ''] ?? 'bg-label-secondary';
@endphp
<div class="card">
  <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
    <h5 class="mb-0">Danh sách hàng hoàn</h5>
    <div class="d-flex gap-2">
      @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))
        <button type="button" class="btn btn-outline-danger js-bulk-toggle"><i class="icon-base bx bx-select-multiple me-1"></i>Xóa hàng loạt</button>
      @endif
      @if(hasPermission('DON_HANG_HOAN_THANH_CREATE'))
        <a href="{{ route('hang-hoan-online.import') }}" class="btn btn-outline-primary"><i class="icon-base bx bx-upload me-1"></i>Import file</a>
        <a href="{{ route('hang-hoan-online.create') }}" class="btn btn-primary"><i class="icon-base bx bx-plus me-1"></i>Nhập tay</a>
      @endif
    </div>
  </div>
  @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))
    @include('content.san-xuat._bulk-delete', ['bulkRoute' => 'hang-hoan-online.bulk-destroy', 'bulkLabel' => 'phiếu hàng hoàn'])
  @endif
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3"><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{ $filters['q'] }}" placeholder="SKU, sản phẩm, màu, size, lý do"></div>
      <div class="col-md-2"><label class="form-label">Seller SKU</label><select class="form-select" name="seller_sku"><option value="">Tất cả</option>@foreach($filterOptions['sellerSkus'] as $sku)<option value="{{ $sku }}" @selected($filters['seller_sku'] === $sku)>{{ $sku }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Trạng thái</label><select class="form-select" name="return_status"><option value="">Tất cả</option>@foreach($filterOptions['statuses'] as $status)<option value="{{ $status }}" @selected($filters['return_status'] === $status)>{{ $status }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Từ ngày</label><input type="date" class="form-control" name="tu_ngay" value="{{ $filters['tu_ngay'] }}"></div>
      <div class="col-md-2"><label class="form-label">Đến ngày</label><input type="date" class="form-control" name="den_ngay" value="{{ $filters['den_ngay'] }}"></div>
      <div class="col-md-1">@include('content.shared._per-page-select', ['perPageColumnClass' => ''])</div>
      <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('hang-hoan-online.index') }}" class="btn btn-outline-secondary">Mới</a></div>
    </form>
  </div>
  <div class="return-table-wrap">
    <table class="table return-table mb-0">
      <thead>
        <tr>
          @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<th class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-check-all" type="checkbox" aria-label="Chọn tất cả"></th>@endif
          <th>Ngày</th>
          <th>Nguồn</th>
          <th>Seller SKU</th>
          <th>SKU Name</th>
          <th>Màu</th>
          <th>Size</th>
          <th class="text-end">SL hoàn</th>
          <th class="text-end">SL cộng tồn</th>
          <th>Trạng thái</th>
          <th>Tình trạng</th>
          <th>Ngày yêu cầu</th>
          <th>Ngày hoàn tiền</th>
          <th>Lý do</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        @forelse($returns as $batch)
          @foreach($batch->chiTiets as $detail)
            <tr>
              @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<td class="bulk-select-cell js-bulk-column d-none"><input class="form-check-input js-bulk-item" type="checkbox" value="{{ $batch->id }}" aria-label="Chọn phiếu hàng hoàn {{ $batch->id }}"></td>@endif
              <td class="return-number">{{ $batch->ngay_hoan->format('d/m/Y') }}</td>
              <td><span class="badge bg-label-{{ $batch->source === 'import' ? 'info' : 'secondary' }}">{{ $batch->source === 'import' ? 'Import' : 'Nhập tay' }}</span></td>
              <td class="fw-semibold">{{ $detail->seller_sku ?: '-' }}</td>
              <td><div class="return-product" title="{{ $detail->sku_name }}">{{ $detail->sku_name ?: '-' }}</div></td>
              <td>{{ $detail->mau ?: '-' }}</td>
              <td>{{ $detail->size ?: '-' }}</td>
              <td class="text-end return-number">{{ number_format((float) $detail->so_luong_hoan, 0, ',', '.') }}</td>
              <td class="text-end return-number {{ $detail->cong_ton ? 'text-success' : 'text-muted' }}">{{ $detail->cong_ton ? number_format((float) $detail->so_luong_hoan, 0, ',', '.') : '0' }}</td>
              <td><span class="badge {{ $statusClass($detail->return_status) }}">{{ $statusLabel($detail->return_status) }}</span></td>
              <td>{{ ['ban_lai_duoc' => 'Bán lại được', 'loi_hong' => 'Lỗi/hỏng', 'cho_kiem' => 'Chờ kiểm'][$detail->tinh_trang_hang] ?? $detail->tinh_trang_hang }}</td>
              <td class="return-number">{{ $detail->time_requested ? $detail->time_requested->format('d/m/Y H:i') : '-' }}</td>
              <td class="return-number">{{ $detail->refund_time ? $detail->refund_time->format('d/m/Y H:i') : '-' }}</td>
              <td>{{ returnReasonVi($detail->return_reason) }}</td>
              <td>
                <div class="d-flex gap-2">
                  @if(hasPermission('DON_HANG_HOAN_THANH_EDIT'))<a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('hang-hoan-online.edit', $batch) }}"><i class="icon-base bx bx-edit"></i></a>@endif
                  @if(hasPermission('DON_HANG_HOAN_THANH_DELETE'))<form method="POST" action="{{ route('hang-hoan-online.destroy', $batch) }}" onsubmit="return confirm('Xóa phiếu hàng hoàn này?')">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-outline-danger"><i class="icon-base bx bx-trash"></i></button></form>@endif
                </div>
              </td>
            </tr>
          @endforeach
        @empty
          <tr><td colspan="{{ hasPermission('DON_HANG_HOAN_THANH_DELETE') ? 15 : 14 }}" class="text-center py-4">Chưa có dữ liệu hàng hoàn.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($returns->hasPages())<div class="card-footer">{{ $returns->links() }}</div>@endif
</div>
@endsection
