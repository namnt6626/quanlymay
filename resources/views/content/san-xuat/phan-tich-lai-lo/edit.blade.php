@extends('layouts/contentNavbarLayout')

@section('title', 'Sửa phân tích lãi lỗ')

@section('page-style')
<style>
  .profit-edit-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  }
  .profit-readonly-metric {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    padding: 1rem;
    min-height: 98px;
  }
  .profit-readonly-metric .label {
    color: var(--bs-secondary-color);
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
  }
  .profit-readonly-metric .value {
    color: var(--bs-heading-color);
    font-size: 1.1rem;
    font-weight: 800;
    margin-top: .45rem;
    font-variant-numeric: tabular-nums;
  }
  .profit-edit-table-wrap {
    overflow-x: auto;
    scrollbar-gutter: stable;
  }
  .profit-edit-table {
    min-width: 1080px;
  }
  .profit-edit-table th {
    background: var(--bs-gray-100);
    color: var(--bs-heading-color);
    font-size: .76rem;
    font-weight: 800;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .profit-edit-table td {
    vertical-align: top;
  }
  .profit-number {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }
  .profit-product-name {
    min-width: 260px;
    max-width: 380px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .profit-cost-input {
    min-width: 120px;
  }
</style>
@endsection

@section('content')
<div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
  <div>
    <h4 class="mb-1">Sửa dữ liệu {{ $period->label }}</h4>
    <div class="text-muted">Chỉ chỉnh giá vốn SKU; các số tổng lấy từ file nguồn được giữ nguyên.</div>
  </div>
  <a href="{{ route('phan-tich-lai-lo.index', ['period' => $period->id]) }}" class="btn btn-outline-secondary">
    <i class="icon-base bx bx-arrow-back me-1"></i>Quay lại
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<form method="POST" action="{{ route('phan-tich-lai-lo.update', $period) }}" class="card">
  @csrf
  @method('PUT')

  <div class="card-header">
    <h5 class="mb-0">Số tổng của tháng</h5>
  </div>
  <div class="card-body">
    <div class="profit-edit-grid">
      <div class="profit-readonly-metric">
        <div class="label">Doanh thu từ file quyết toán TikTok</div>
        <div class="value">{{ number_format((float) $period->total_revenue, 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-readonly-metric">
        <div class="label">Doanh thu từ file tất cả đơn hàng/SKU</div>
        <div class="value">{{ number_format((float) ($period->sku_revenue_total ?? 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-readonly-metric">
        <div class="label">DT đơn hàng trước khi trừ hoàn/trả</div>
        <div class="value">{{ number_format((float) ($period->sku_gross_revenue_total ?? 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-readonly-metric">
        <div class="label">Tiền hoàn/trả hệ thống đã trừ</div>
        <div class="value">{{ number_format((float) ($period->sku_refund_total ?? 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-readonly-metric">
        <div class="label">Chênh lệch đã chia về từng mã</div>
        <div class="value">{{ number_format((float) ($period->revenue_adjustment ?? 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-readonly-metric">
        <div class="label">Phí sàn</div>
        <div class="value">{{ number_format((float) $period->marketplace_fees, 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-readonly-metric">
        <div class="label">Tổng chi phí QC</div>
        <div class="value">{{ number_format((float) $period->ad_cost, 0, ',', '.') }} ₫</div>
      </div>
      @if((float) data_get($period->source_totals, 'ads.cost_per_order', 0) > 0)
        <div class="profit-readonly-metric">
          <div class="label">Chi phí QC mỗi đơn hàng</div>
          <div class="value">{{ number_format((float) data_get($period->source_totals, 'ads.cost_per_order', 0), 0, ',', '.') }} ₫</div>
        </div>
      @endif
      <div class="profit-readonly-metric">
        <div class="label">Đơn hoàn tất</div>
        <div class="value">{{ number_format((float) ($period->completed_order_count ?: $period->order_count), 0, ',', '.') }}</div>
      </div>
      @if((float) ($period->analytics_order_count ?? 0) > 0)
        <div class="profit-readonly-metric">
          <div class="label">Đơn theo file phân tích</div>
          <div class="value">{{ number_format((float) ($period->analytics_order_count ?? 0), 0, ',', '.') }}</div>
        </div>
      @endif
      <div class="profit-readonly-metric">
        <div class="label">Số món bán</div>
        <div class="value">{{ number_format((float) $period->item_count, 0, ',', '.') }}</div>
      </div>
    </div>
    <div class="text-muted mt-3">Muốn thay đổi các số tổng, hãy nhập lại bộ file của tháng đó để dữ liệu không bị lệch nguồn.</div>
  </div>

  <div class="card-header border-top d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
    <div>
      <h5 class="mb-1">Giá vốn theo SKU</h5>
      <div class="text-muted">Sửa trực tiếp giá vốn/sp cho mã cần chỉnh.</div>
    </div>
    <span class="badge bg-label-primary">{{ number_format($period->skuSummaries->count(), 0, ',', '.') }} SKU</span>
  </div>
  <div class="profit-edit-table-wrap">
    <table class="table profit-edit-table mb-0">
      <thead>
        <tr>
          <th>Mã FOB</th>
          <th>Seller SKU</th>
          <th>Sản phẩm</th>
          <th class="text-end">SL ròng</th>
          <th class="text-end">DT từ file tất cả đơn hàng/SKU</th>
          <th class="text-end">Chênh lệch chia về từng mã</th>
          <th class="text-end">DT sau khi chia chênh lệch</th>
          <th>Giá vốn/sp</th>
          <th class="text-end">Lãi/lỗ hiện tại</th>
        </tr>
      </thead>
      <tbody>
        @foreach($period->skuSummaries as $sku)
          <tr>
            <td>{{ $sku->fob_sku ?: '-' }}</td>
            <td class="fw-semibold">{{ $sku->seller_sku }}</td>
            <td><div class="profit-product-name" title="{{ $sku->product_name }}">{{ $sku->product_name ?: '-' }}</div></td>
            <td class="text-end profit-number">{{ number_format((float) $sku->net_quantity, 0, ',', '.') }}</td>
            <td class="text-end profit-number">{{ number_format((float) ($sku->original_revenue ?: $sku->revenue), 0, ',', '.') }} ₫</td>
            <td class="text-end profit-number">{{ number_format((float) ($sku->allocated_revenue_adjustment ?? 0), 0, ',', '.') }} ₫</td>
            <td class="text-end profit-number">{{ number_format((float) $sku->revenue, 0, ',', '.') }} ₫</td>
            <td>
              <input class="form-control form-control-sm profit-cost-input" name="sku_costs[{{ $sku->id }}]" inputmode="decimal" value="{{ old('sku_costs.'.$sku->id, number_format((float) $sku->unit_cost, 0, ',', '.')) }}">
            </td>
            <td class="text-end profit-number fw-semibold {{ $sku->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sku->profit, 0, ',', '.') }} ₫</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ route('phan-tich-lai-lo.index', ['period' => $period->id]) }}" class="btn btn-outline-secondary">Hủy</a>
    <button class="btn btn-primary" onclick="return confirm('Lưu thay đổi và tính lại {{ $period->label }}?')">
      <i class="icon-base bx bx-save me-1"></i>Lưu thay đổi
    </button>
  </div>
</form>
@endsection
