@extends('layouts/contentNavbarLayout')

@section('title', 'Xác nhận phân tích lãi lỗ')

@section('page-style')
<style>
  .profit-preview-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
  .profit-preview-metric {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    padding: 1rem;
  }
  .profit-preview-metric .label {
    color: var(--bs-secondary-color);
    font-size: .8rem;
    font-weight: 800;
    text-transform: uppercase;
  }
  .profit-preview-metric .value {
    margin-top: .45rem;
    font-size: 1.2rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
  }
  .profit-map-wrap {
    overflow-x: auto;
    scrollbar-gutter: stable;
  }
  .profit-map-table {
    min-width: 1180px;
  }
  .profit-map-table th {
    background: var(--bs-gray-100);
    color: var(--bs-heading-color);
    font-size: .76rem;
    font-weight: 800;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .profit-map-table td {
    vertical-align: top;
  }
  .profit-number {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }
  .profit-product {
    min-width: 260px;
    max-width: 380px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .profit-map-input {
    min-width: 130px;
  }
  .profit-missing-orders-scroll {
    max-height: 65vh;
    overflow: auto;
  }
  .profit-missing-orders-table {
    min-width: 760px;
  }
  .profit-missing-orders-table th {
    position: sticky;
    top: 0;
    background: var(--bs-gray-100);
    z-index: 1;
  }
</style>
@endsection

@section('content')
<div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
  <div>
    <h4 class="mb-1">Xác nhận dữ liệu {{ $preview['period']['label'] }}</h4>
    <div class="text-muted">
      @if($preview['period']['detected_start'] && $preview['period']['detected_end'])
        File phát hiện kỳ {{ \Carbon\Carbon::parse($preview['period']['detected_start'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($preview['period']['detected_end'])->format('d/m/Y') }}
      @else
        Chưa nhận diện được khoảng ngày trong file
      @endif
    </div>
  </div>
  <a href="{{ route('phan-tich-lai-lo.create') }}" class="btn btn-outline-secondary">
    <i class="icon-base bx bx-arrow-back me-1"></i>Upload lại
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

@isset($commitError)
  <div class="alert alert-danger">{{ $commitError }}</div>
@endisset

@if($preview['period']['existing_period_id'])
  <div class="alert alert-warning">
    {{ $preview['period']['label'] }} đã có dữ liệu. Khi bấm xác nhận, toàn bộ dữ liệu tháng cũ sẽ được xóa và thay bằng bộ dữ liệu này.
  </div>
@endif

@php
  $marketplaceLabel = data_get($preview, 'period.marketplace_label', 'TikTok');
  $settlementFilter = data_get($preview, 'source_totals.orders.settlement_filter', []);
  $missingSettlementRevenue = abs((float) data_get($settlementFilter, 'missing_revenue', 0));
  $settlementOrderCount = (int) data_get($settlementFilter, 'settlement_order_count', 0);
  $matchedOrderCount = (int) data_get($settlementFilter, 'matched_order_count', 0);
  $missingOrderCount = (int) data_get($settlementFilter, 'missing_order_count', 0);
  $missingOrders = data_get($settlementFilter, 'missing_orders', []);
  $settlementCreatedStart = data_get($preview, 'source_totals.settlement.order_created_start');
  $settlementCreatedEnd = data_get($preview, 'source_totals.settlement.order_created_end');
@endphp
@if(data_get($settlementFilter, 'enabled'))
  <div class="alert {{ $missingSettlementRevenue > 0.5 ? 'alert-danger' : (data_get($settlementFilter, 'missing_order_count', 0) > 0 ? 'alert-warning' : 'alert-success') }}">
    @if($settlementOrderCount > 0 && $matchedOrderCount === 0)
      <div class="fw-semibold mb-1">Không lấy được số liệu bán hàng vì file tất cả đơn hàng/SKU không có mã đơn nào trùng với file quyết toán {{ $marketplaceLabel }}.</div>
      <div>
        File quyết toán có {{ number_format($settlementOrderCount, 0, ',', '.') }} đơn.
        @if($settlementCreatedStart && $settlementCreatedEnd)
          Các đơn trong file quyết toán được tạo từ {{ \Carbon\Carbon::parse($settlementCreatedStart)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($settlementCreatedEnd)->format('d/m/Y') }}.
          Vui lòng xuất lại file tất cả đơn hàng/SKU theo cột Thời gian tạo đơn hàng trong khoảng này.
        @else
          Vui lòng xuất lại file tất cả đơn hàng/SKU theo khoảng Thời gian tạo đơn hàng của các đơn trong file quyết toán.
        @endif
        File quyết toán gồm {{ number_format((float) data_get($preview, 'source_totals.settlement.positive_order_count', 0), 0, ',', '.') }} đơn dương tiền
        và {{ number_format((float) data_get($preview, 'source_totals.settlement.negative_order_count', 0), 0, ',', '.') }} đơn âm tiền.
        @if($missingOrderCount > 0)
          <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#missingOrdersModal">
            Xem đơn thiếu
          </button>
        @endif
      </div>
    @else
      File tất cả đơn hàng/SKU đã lọc theo mã đơn trong file quyết toán {{ $marketplaceLabel }}:
      tìm thấy {{ number_format($matchedOrderCount, 0, ',', '.') }}/{{ number_format($settlementOrderCount, 0, ',', '.') }} đơn.
      @if($missingOrderCount > 0)
        Còn thiếu {{ number_format($missingOrderCount, 0, ',', '.') }} đơn,
        doanh thu thiếu {{ number_format((float) data_get($settlementFilter, 'missing_revenue', 0), 0, ',', '.') }} ₫.
        @if($settlementCreatedStart && $settlementCreatedEnd)
          Vui lòng xuất lại file tất cả đơn hàng/SKU theo cột Thời gian tạo đơn hàng từ {{ \Carbon\Carbon::parse($settlementCreatedStart)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($settlementCreatedEnd)->format('d/m/Y') }}.
        @else
          Vui lòng xuất lại file tất cả đơn hàng/SKU bao phủ đủ ngày tạo đơn trong file quyết toán.
        @endif
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-bs-toggle="modal" data-bs-target="#missingOrdersModal">
          Xem đơn thiếu
        </button>
      @endif
    @endif
  </div>
@endif

@if($missingOrderCount > 0)
  <div class="modal fade" id="missingOrdersModal" tabindex="-1" aria-labelledby="missingOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="missingOrdersModalLabel">Đơn có trong quyết toán nhưng thiếu trong file tất cả đơn hàng/SKU</h5>
            <div class="text-muted small">{{ number_format($missingOrderCount, 0, ',', '.') }} đơn thiếu, doanh thu thiếu {{ number_format((float) data_get($settlementFilter, 'missing_revenue', 0), 0, ',', '.') }} ₫</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
        </div>
        <div class="modal-body">
          <div class="profit-missing-orders-scroll">
            <table class="table table-sm profit-missing-orders-table mb-0">
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Ngày tạo đơn</th>
                  <th>Ngày quyết toán</th>
                  <th class="text-end">Doanh thu quyết toán</th>
                  <th class="text-end">Tiền thực nhận</th>
                </tr>
              </thead>
              <tbody>
                @foreach($missingOrders as $order)
                  <tr>
                    <td class="fw-semibold">{{ data_get($order, 'order_id') }}</td>
                    <td>{{ data_get($order, 'created_at') ?: '-' }}</td>
                    <td>{{ data_get($order, 'settled_at') ?: '-' }}</td>
                    <td class="text-end profit-number {{ (float) data_get($order, 'revenue', 0) < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) data_get($order, 'revenue', 0), 0, ',', '.') }} ₫</td>
                    <td class="text-end profit-number">{{ number_format((float) data_get($order, 'settlement_amount', 0), 0, ',', '.') }} ₫</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        </div>
      </div>
    </div>
  </div>
@endif

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Tổng quan file đã đọc</h5></div>
  <div class="card-body">
    <div class="profit-preview-grid">
      @if((float) $preview['summary']['gmv'] > 0)
        <div class="profit-preview-metric">
          <div class="label">Tổng doanh số đặt hàng (GMV)</div>
          <div class="value">{{ number_format((float) $preview['summary']['gmv'], 0, ',', '.') }} ₫</div>
        </div>
      @endif
      <div class="profit-preview-metric">
        <div class="label">Doanh thu từ file quyết toán {{ $marketplaceLabel }}</div>
        <div class="value">{{ number_format((float) $preview['summary']['settlement_revenue'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Doanh thu từ file tất cả đơn hàng/SKU</div>
        <div class="value">{{ number_format((float) $preview['summary']['sku_revenue_total'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Đơn trong file quyết toán {{ $marketplaceLabel }}</div>
        <div class="value">{{ number_format((float) data_get($settlementFilter, 'settlement_order_count', 0), 0, ',', '.') }}</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Đơn quyết toán dương tiền</div>
        <div class="value text-success">{{ number_format((float) data_get($preview, 'source_totals.settlement.positive_order_count', 0), 0, ',', '.') }}</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Tổng tiền đơn dương</div>
        <div class="value text-success">{{ number_format((float) data_get($preview, 'source_totals.settlement.positive_order_revenue', 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Đơn quyết toán âm tiền</div>
        <div class="value text-danger">{{ number_format((float) data_get($preview, 'source_totals.settlement.negative_order_count', 0), 0, ',', '.') }}</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Tổng tiền đơn âm</div>
        <div class="value text-danger">{{ number_format((float) data_get($preview, 'source_totals.settlement.negative_order_revenue', 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Đơn quyết toán tìm thấy trong file SKU</div>
        <div class="value">{{ number_format((float) data_get($settlementFilter, 'matched_order_count', 0), 0, ',', '.') }}</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Đơn quyết toán còn thiếu trong file SKU</div>
        <div class="value {{ data_get($settlementFilter, 'missing_order_count', 0) > 0 ? 'text-warning' : 'text-success' }}">{{ number_format((float) data_get($settlementFilter, 'missing_order_count', 0), 0, ',', '.') }}</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Doanh thu của đơn còn thiếu</div>
        <div class="value {{ $missingSettlementRevenue > 0.5 ? 'text-danger' : 'text-success' }}">{{ number_format((float) data_get($settlementFilter, 'missing_revenue', 0), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">DT đơn hàng trước khi trừ hoàn/trả</div>
        <div class="value">{{ number_format((float) $preview['summary']['sku_gross_revenue_total'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Tiền hoàn/trả hệ thống đã trừ</div>
        <div class="value text-danger">{{ number_format((float) $preview['summary']['sku_refund_total'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Chênh lệch đã chia về từng mã</div>
        <div class="value {{ abs((float) $preview['summary']['revenue_adjustment']) > 0 ? 'text-warning' : 'text-success' }}">{{ number_format((float) $preview['summary']['revenue_adjustment'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Phí sàn</div>
        <div class="value">{{ number_format((float) $preview['summary']['marketplace_fees'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Chi phí QC mỗi đơn hàng</div>
        <div class="value">{{ number_format((float) ($preview['summary']['ad_cost_per_order'] ?? data_get($preview, 'source_totals.ads.cost_per_order', 0)), 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Tổng chi phí QC</div>
        <div class="value">{{ number_format((float) $preview['summary']['ad_cost'], 0, ',', '.') }} ₫</div>
      </div>
      <div class="profit-preview-metric">
        <div class="label">Đơn hoàn tất</div>
        <div class="value">{{ number_format((float) $preview['summary']['completed_order_count'], 0, ',', '.') }}</div>
      </div>
      @if((float) $preview['summary']['analytics_order_count'] > 0)
        <div class="profit-preview-metric">
          <div class="label">Đơn theo file phân tích</div>
          <div class="value">{{ number_format((float) $preview['summary']['analytics_order_count'], 0, ',', '.') }}</div>
        </div>
      @endif
      <div class="profit-preview-metric">
        <div class="label">SKU cần bổ sung</div>
        <div class="value {{ $preview['summary']['missing_cost_count'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($preview['summary']['missing_cost_count'], 0, ',', '.') }}</div>
      </div>
    </div>
  </div>
</div>

<form method="POST" action="{{ route('phan-tich-lai-lo.commit') }}" class="card">
  @csrf
  <input type="hidden" name="preview_key" value="{{ $previewKey }}">
  <div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
    <div>
      <h5 class="mb-1">Map SKU và giá vốn</h5>
      <div class="text-muted">Các dòng thiếu giá vốn phải nhập trước khi xác nhận cập nhật tháng.</div>
    </div>
    <span class="badge {{ $preview['summary']['missing_cost_count'] > 0 ? 'bg-label-danger' : 'bg-label-success' }}">
      {{ $preview['summary']['auto_mapped_count'] }} mã đã map
    </span>
  </div>
  <div class="profit-map-wrap">
    <table class="table profit-map-table mb-0">
      <thead>
        <tr>
          <th>Mã FOB</th>
          <th>Seller SKU</th>
          <th>Sản phẩm</th>
          <th class="text-end">SL bán</th>
          <th class="text-end">SL hoàn</th>
          <th class="text-end">SL ròng</th>
          <th class="text-end">Doanh thu từ file tất cả đơn hàng/SKU</th>
          <th>Giá vốn/sp</th>
          <th>Map</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody>
        @foreach($preview['sku_rows'] as $row)
          <tr>
            <td class="fw-semibold">{{ $row['fob_sku'] ?: '' }}</td>
            <td class="fw-semibold">{{ $row['seller_sku'] }}</td>
            <td><div class="profit-product" title="{{ $row['product_name'] }}">{{ $row['product_name'] ?: '-' }}</div></td>
            <td class="text-end profit-number">{{ number_format((float) $row['quantity_sold'], 0, ',', '.') }}</td>
            <td class="text-end profit-number">{{ number_format((float) $row['quantity_returned'], 0, ',', '.') }}</td>
            <td class="text-end profit-number fw-semibold">{{ number_format((float) $row['net_quantity'], 0, ',', '.') }}</td>
            <td class="text-end profit-number">{{ number_format((float) $row['revenue'], 0, ',', '.') }} ₫</td>
            <td>
              <input type="text" inputmode="decimal" class="form-control form-control-sm profit-map-input" name="sku_maps[{{ $row['key'] }}][unit_cost]" value="{{ old('sku_maps.'.$row['key'].'.unit_cost', (float) $row['unit_cost'] > 0 ? number_format((float) $row['unit_cost'], 0, ',', '.') : '') }}" placeholder="Giá vốn">
            </td>
            <td>
              <span class="badge {{ in_array($row['confidence'], ['HIGH', 'SAVED'], true) ? 'bg-label-success' : ($row['confidence'] === 'MEDIUM' ? 'bg-label-warning' : 'bg-label-secondary') }}">{{ $row['confidence'] }}</span>
              <div class="small text-muted mt-1">{{ $row['map_reason'] }}</div>
            </td>
            <td>
              @if($row['needs_cost'])
                <span class="badge bg-label-danger">Cần giá vốn</span>
              @else
                <span class="badge bg-label-success">Đủ dữ liệu</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
    <div class="text-muted">Sau khi xác nhận, dữ liệu {{ $preview['period']['label'] }} sẽ trở thành bộ thống kê chính của tháng.</div>
    <div class="d-flex gap-2">
      <a href="{{ route('phan-tich-lai-lo.create') }}" class="btn btn-outline-secondary">Hủy</a>
      <button class="btn btn-primary" @disabled($missingSettlementRevenue > 0.5) onclick="return confirm('Xác nhận cập nhật dữ liệu {{ $preview['period']['label'] }}?')">
        <i class="icon-base bx bx-check me-1"></i>Xác nhận cập nhật tháng
      </button>
    </div>
  </div>
</form>
@endsection
