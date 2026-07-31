@extends('layouts/contentNavbarLayout')
@section('title', 'Xác nhận import hàng hoàn')
@section('page-style')
<style>
  .return-preview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; }
  .return-preview-metric { border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: 1rem; }
  .return-preview-metric .label { color: var(--bs-secondary-color); font-size: .78rem; font-weight: 800; text-transform: uppercase; }
  .return-preview-metric .value { margin-top: .4rem; font-size: 1.2rem; font-weight: 800; }
  .return-preview-wrap { max-height: 62vh; overflow: auto; scrollbar-gutter: stable; }
  .return-preview-table { min-width: 1180px; }
  .return-preview-table th { position: sticky; top: 0; background: var(--bs-gray-100); z-index: 1; white-space: nowrap; font-size: .76rem; text-transform: uppercase; }
</style>
@endsection
@section('content')
@php
  $displayRows = $preview['display_rows'] ?? $preview['rows'];
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
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Xác nhận import hàng hoàn</h4>
    <div class="text-muted">{{ $preview['file_name'] ?? '' }}</div>
  </div>
  <a href="{{ route('hang-hoan-online.import') }}" class="btn btn-outline-secondary">Chọn file khác</a>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Tổng quan file</h5></div>
  <div class="card-body">
    <div class="return-preview-grid">
      <div class="return-preview-metric"><div class="label">Tổng dòng</div><div class="value">{{ number_format($preview['summary']['row_count'], 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">Dòng mới hiển thị</div><div class="value text-primary">{{ number_format($preview['summary']['new_row_count'] ?? count($displayRows), 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">Dòng trùng đã ẩn</div><div class="value text-warning">{{ number_format($preview['summary']['duplicate_row_count'] ?? 0, 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">Tổng SL hoàn trong file</div><div class="value">{{ number_format((float) $preview['summary']['return_quantity'], 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">SL hoàn mới</div><div class="value text-primary">{{ number_format((float) ($preview['summary']['new_return_quantity'] ?? 0), 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">Dòng cộng tồn mới</div><div class="value text-success">{{ number_format($preview['summary']['stock_row_count'], 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">SL cộng tồn mới</div><div class="value text-success">{{ number_format((float) $preview['summary']['stock_quantity'], 0, ',', '.') }}</div></div>
      <div class="return-preview-metric"><div class="label">Từ ngày</div><div class="value">{{ $preview['summary']['from_date'] ? \Carbon\Carbon::parse($preview['summary']['from_date'])->format('d/m/Y') : '-' }}</div></div>
      <div class="return-preview-metric"><div class="label">Đến ngày</div><div class="value">{{ $preview['summary']['to_date'] ? \Carbon\Carbon::parse($preview['summary']['to_date'])->format('d/m/Y') : '-' }}</div></div>
    </div>
  </div>
</div>

<form method="POST" action="{{ route('hang-hoan-online.commit') }}" class="card">
  @csrf
  <input type="hidden" name="preview_key" value="{{ $previewKey }}">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-1">Chi tiết dòng mới</h5>
      <div class="text-muted small">Các dòng đã có trong hệ thống hoặc bị lặp trong file đã được ẩn khỏi bảng này.</div>
    </div>
    <button class="btn btn-primary"><i class="icon-base bx bx-check me-1"></i>Xác nhận import</button>
  </div>
  <div class="return-preview-wrap">
    <table class="table return-preview-table mb-0">
      <thead>
        <tr>
          <th>Seller SKU</th>
          <th>SKU Name</th>
          <th>Màu</th>
          <th>Size</th>
          <th class="text-end">SL</th>
          <th>Trạng thái</th>
          <th>Cộng tồn</th>
          <th>Ngày yêu cầu</th>
          <th>Ngày hoàn tiền</th>
          <th>Lý do</th>
        </tr>
      </thead>
      <tbody>
        @forelse($displayRows as $row)
          <tr>
            <td class="fw-semibold">{{ $row['seller_sku'] ?: '-' }}</td>
            <td style="max-width:360px">{{ $row['sku_name'] ?: '-' }}</td>
            <td>{{ $row['mau'] ?: '-' }}</td>
            <td>{{ $row['size'] ?: '-' }}</td>
            <td class="text-end">{{ number_format((float) $row['so_luong_hoan'], 0, ',', '.') }}</td>
            <td><span class="badge {{ $statusClass($row['return_status'] ?? null) }}">{{ $statusLabel($row['return_status'] ?? null) }}</span></td>
            <td><span class="badge {{ $row['cong_ton'] ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $row['cong_ton'] ? 'Có' : 'Không' }}</span></td>
            <td>{{ $row['time_requested'] ? \Carbon\Carbon::parse($row['time_requested'])->format('d/m/Y H:i') : '-' }}</td>
            <td>{{ $row['refund_time'] ? \Carbon\Carbon::parse($row['refund_time'])->format('d/m/Y H:i') : '-' }}</td>
            <td>{{ returnReasonVi($row['return_reason'] ?? null) }}</td>
          </tr>
        @empty
          <tr><td colspan="10" class="text-center py-4">Không có dòng mới để hiển thị. Nếu xác nhận import, hệ thống chỉ cập nhật các dòng trùng đã có.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</form>
@endsection
