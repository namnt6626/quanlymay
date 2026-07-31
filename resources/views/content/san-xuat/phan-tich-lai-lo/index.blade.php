@extends('layouts/contentNavbarLayout')

@section('title', 'Phân tích lãi lỗ')

@section('page-style')
<style>
  .profit-kpi-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
  .profit-kpi {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    padding: 1rem;
    min-height: 116px;
  }
  .profit-kpi-label {
    color: var(--bs-secondary-color);
    font-size: .82rem;
    font-weight: 700;
    text-transform: uppercase;
  }
  .profit-kpi-value {
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.2;
    margin-top: .5rem;
    font-variant-numeric: tabular-nums;
  }
  .profit-table-wrap {
    overflow-x: auto;
    scrollbar-gutter: stable;
  }
  .profit-sku-table {
    min-width: 1280px;
  }
  .profit-sku-table th {
    background: var(--bs-gray-100);
    color: var(--bs-heading-color);
    font-size: .76rem;
    font-weight: 800;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .profit-sku-table td {
    vertical-align: top;
  }
  .profit-number {
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }
  .profit-sku-name {
    min-width: 150px;
    font-weight: 700;
  }
  .profit-product-name {
    min-width: 260px;
    max-width: 360px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .profit-signal-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  }
  .profit-signal-list {
    display: grid;
    gap: .75rem;
    max-height: 520px;
    overflow-y: auto;
    padding-right: .35rem;
    scrollbar-gutter: stable;
  }
  .profit-signal-row {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    padding: .85rem;
  }
  .profit-signal-sku {
    font-weight: 800;
    color: var(--bs-heading-color);
  }
  .profit-signal-meta {
    color: var(--bs-secondary-color);
    font-size: .86rem;
  }
  .profit-period-select {
    min-width: min(100%, 240px);
  }
</style>
@endsection

@section('content')
@include('content.danh-muc._toast')

<div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
  <div>
    <h4 class="mb-1">Phân tích lãi lỗ</h4>
    <div class="text-muted">Mỗi tháng giữ riêng dữ liệu TikTok và Shopee; tab Tổng quan cộng gộp theo mã FOB.</div>
  </div>
  @if (hasPermission('PHAN_TICH_LAI_LO_CREATE'))
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('phan-tich-lai-lo.create-marketplace', 'tiktok') }}" class="btn btn-outline-primary">
        <i class="icon-base bx bx-upload me-1"></i>Nhập dữ liệu TikTok
      </a>
      <a href="{{ route('phan-tich-lai-lo.create-marketplace', 'shopee') }}" class="btn btn-outline-primary">
        <i class="icon-base bx bx-upload me-1"></i>Nhập dữ liệu Shopee
      </a>
    </div>
  @endif
</div>

@if($selectedPeriod)
  @php
    $tabPeriods = [
      'total' => $selectedPeriod,
      'shopee' => $shopeePeriod,
      'tiktok' => $tiktokPeriod,
    ];
    $selectedPeriod = $tabPeriods[$activeTab];
    $selectedMarketplaceLabel = $activeTab === 'total' ? 'Tổng quan' : ($activeTab === 'shopee' ? 'Shopee' : 'TikTok');
    $revenueLabel = $activeTab === 'total' ? 'Doanh thu tổng hợp' : 'Doanh thu từ file quyết toán '.$selectedMarketplaceLabel;
    $skuRevenueLabel = $activeTab === 'total' ? 'Doanh thu SKU đã cộng gộp' : 'Doanh thu từ file tất cả đơn hàng/SKU';
    $grossRevenueLabel = $activeTab === 'total' ? 'DT SKU trước khi trừ hoàn/trả' : 'DT đơn hàng trước khi trừ hoàn/trả';
    $adjustmentLabel = $activeTab === 'total' ? 'Chênh lệch tổng đã phân bổ' : 'Chênh lệch đã chia về từng mã';
    $listedPeriods = $activeTab === 'total' ? $periods : $periods->getCollection()->where('marketplace', $activeTab);
  @endphp
  <div class="card mb-4">
    <div class="card-body d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-end">
      <form method="GET" action="{{ route('phan-tich-lai-lo.index') }}" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-end">
        <div>
          <label class="form-label">Chọn tháng xem</label>
          <select class="form-select profit-period-select" name="month" onchange="this.form.submit()">
            @foreach($monthOptions as $value => $label)
              <option value="{{ $value }}" @selected($selectedMonth === $value)>{{ $label }}</option>
            @endforeach
          </select>
          <input type="hidden" name="tab" value="{{ $activeTab }}">
        </div>
      </form>
      <div class="text-muted">Đang xem {{ $selectedPeriod?->label ?? $selectedMarketplaceLabel }}</div>
    </div>
  </div>

  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link {{ $activeTab === 'total' ? 'active' : '' }}" href="{{ route('phan-tich-lai-lo.index', ['month' => $selectedMonth, 'tab' => 'total']) }}">Tổng quan</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $activeTab === 'shopee' ? 'active' : '' }}" href="{{ route('phan-tich-lai-lo.index', ['month' => $selectedMonth, 'tab' => 'shopee']) }}">Shopee</a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $activeTab === 'tiktok' ? 'active' : '' }}" href="{{ route('phan-tich-lai-lo.index', ['month' => $selectedMonth, 'tab' => 'tiktok']) }}">TikTok</a>
    </li>
  </ul>

  @if(!$selectedPeriod)
    <div class="card mb-4">
      <div class="card-body text-center py-5">
        <h5 class="mb-2">Chưa có dữ liệu {{ $activeTab === 'shopee' ? 'Shopee' : 'TikTok' }} tháng này</h5>
        @if (hasPermission('PHAN_TICH_LAI_LO_CREATE'))
          <a href="{{ route('phan-tich-lai-lo.create-marketplace', $activeTab) }}" class="btn btn-primary">Nhập dữ liệu {{ $activeTab === 'shopee' ? 'Shopee' : 'TikTok' }}</a>
        @endif
      </div>
    </div>
  @else

  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <div>
        <h5 class="mb-1">{{ $selectedPeriod->label }}</h5>
        <div class="text-muted">
          @if($selectedPeriod->period_start && $selectedPeriod->period_end)
            {{ $selectedPeriod->period_start->format('d/m/Y') }} - {{ $selectedPeriod->period_end->format('d/m/Y') }}
          @else
            Chưa nhận diện khoảng ngày trong file
          @endif
        </div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        @if($selectedPeriod->id !== 'all' && hasPermission('PHAN_TICH_LAI_LO_EDIT'))
          <a class="btn btn-sm btn-outline-primary" href="{{ route('phan-tich-lai-lo.edit', $selectedPeriod->id) }}">
            <i class="icon-base bx bx-edit me-1"></i>Sửa dữ liệu
          </a>
        @endif
        <span class="badge bg-label-success">Đã chốt {{ optional($selectedPeriod->confirmed_at)->format('d/m/Y H:i') }}</span>
      </div>
    </div>
    <div class="card-body">
      <div class="profit-kpi-grid">
        <div class="profit-kpi">
          <div class="profit-kpi-label">{{ $revenueLabel }}</div>
          <div class="profit-kpi-value">{{ number_format((float) $selectedPeriod->total_revenue, 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">{{ $skuRevenueLabel }}</div>
          <div class="profit-kpi-value">{{ number_format((float) ($selectedPeriod->sku_revenue_total ?? 0), 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">{{ $grossRevenueLabel }}</div>
          <div class="profit-kpi-value">{{ number_format((float) ($selectedPeriod->sku_gross_revenue_total ?? 0), 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Tiền hoàn/trả hệ thống đã trừ</div>
          <div class="profit-kpi-value text-danger">{{ number_format((float) ($selectedPeriod->sku_refund_total ?? 0), 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">{{ $adjustmentLabel }}</div>
          <div class="profit-kpi-value {{ abs((float) ($selectedPeriod->revenue_adjustment ?? 0)) > 0 ? 'text-warning' : 'text-success' }}">{{ number_format((float) ($selectedPeriod->revenue_adjustment ?? 0), 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Tổng chi phí</div>
          <div class="profit-kpi-value">{{ number_format((float) $selectedPeriod->total_cost, 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Lãi/lỗ toàn shop</div>
          <div class="profit-kpi-value {{ $selectedPeriod->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $selectedPeriod->profit, 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Lãi trên mỗi đơn</div>
          <div class="profit-kpi-value {{ $selectedPeriod->profit_per_order >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $selectedPeriod->profit_per_order, 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Đơn hoàn tất</div>
          <div class="profit-kpi-value">{{ number_format((float) ($selectedPeriod->completed_order_count ?: $selectedPeriod->order_count), 0, ',', '.') }}</div>
        </div>
        @if((float) ($selectedPeriod->analytics_order_count ?? 0) > 0)
          <div class="profit-kpi">
            <div class="profit-kpi-label">Đơn theo file phân tích</div>
            <div class="profit-kpi-value">{{ number_format((float) ($selectedPeriod->analytics_order_count ?? 0), 0, ',', '.') }}</div>
          </div>
        @endif
        @if((float) data_get($selectedPeriod->source_totals, 'ads.cost_per_order', 0) > 0)
          <div class="profit-kpi">
            <div class="profit-kpi-label">QC mỗi đơn hàng</div>
            <div class="profit-kpi-value">{{ number_format((float) data_get($selectedPeriod->source_totals, 'ads.cost_per_order', 0), 0, ',', '.') }} ₫</div>
          </div>
        @endif
        <div class="profit-kpi">
          <div class="profit-kpi-label">QC tối đa hòa vốn</div>
          <div class="profit-kpi-value">{{ number_format((float) $selectedPeriod->ad_breakeven, 0, ',', '.') }} ₫</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Mã đang lãi</div>
          <div class="profit-kpi-value text-success">{{ number_format($selectedPeriod->skuSummaries->where('status', 'profit')->count(), 0, ',', '.') }}</div>
        </div>
        <div class="profit-kpi">
          <div class="profit-kpi-label">Mã càng bán càng lỗ</div>
          <div class="profit-kpi-value text-danger">{{ number_format($selectedPeriod->skuSummaries->where('status', 'loss')->count(), 0, ',', '.') }}</div>
        </div>
      </div>
    </div>
  </div>

  @if($activeTab === 'total' && !empty($selectedPeriod->marketplaceBreakdown))
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">So sánh nền tảng</h5></div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Nền tảng</th>
              <th class="text-end">Doanh thu</th>
              <th class="text-end">Đơn</th>
              <th class="text-end">SL bán ròng</th>
              <th class="text-end">Giá vốn</th>
              <th class="text-end">Phí</th>
              <th class="text-end">QC</th>
              <th class="text-end">Lãi/lỗ</th>
            </tr>
          </thead>
          <tbody>
            @foreach($selectedPeriod->marketplaceBreakdown as $periodBreakdown)
              <tr>
                <td><span class="badge bg-label-{{ $periodBreakdown->marketplace === 'shopee' ? 'warning' : 'info' }}">{{ $periodBreakdown->marketplace_label }}</span></td>
                <td class="text-end profit-number">{{ number_format((float) $periodBreakdown->total_revenue, 0, ',', '.') }} ₫</td>
                <td class="text-end profit-number">{{ number_format((float) ($periodBreakdown->completed_order_count ?: $periodBreakdown->order_count), 0, ',', '.') }}</td>
                <td class="text-end profit-number">{{ number_format((float) $periodBreakdown->item_count, 0, ',', '.') }}</td>
                <td class="text-end profit-number">{{ number_format((float) $periodBreakdown->cogs, 0, ',', '.') }} ₫</td>
                <td class="text-end profit-number">{{ number_format((float) $periodBreakdown->marketplace_fees, 0, ',', '.') }} ₫</td>
                <td class="text-end profit-number">{{ number_format((float) $periodBreakdown->ad_cost, 0, ',', '.') }} ₫</td>
                <td class="text-end profit-number fw-semibold {{ $periodBreakdown->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $periodBreakdown->profit, 0, ',', '.') }} ₫</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @php
    $profitableSkus = $selectedPeriod->skuSummaries
      ->where('status', 'profit')
      ->sortByDesc('profit')
      ->values();
    $lossSkus = $selectedPeriod->skuSummaries
      ->where('status', 'loss')
      ->sortByDesc('net_quantity')
      ->values();
  @endphp
  <div class="profit-signal-grid mb-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Mã hàng đang lãi</h5>
        <span class="badge bg-label-success">{{ number_format($selectedPeriod->skuSummaries->where('status', 'profit')->count(), 0, ',', '.') }} mã</span>
      </div>
      <div class="card-body">
        <div class="profit-signal-list" id="profit-list">
          @forelse($profitableSkus as $sku)
            <div class="profit-signal-row">
              <div class="d-flex justify-content-between gap-3">
                <div>
                  <div class="profit-signal-sku">{{ $sku->fob_sku ?: $sku->seller_sku }}</div>
                  <div class="profit-signal-meta">{{ $sku->seller_sku }}</div>
                </div>
                <div class="text-end">
                  <div class="text-success fw-semibold profit-number">{{ number_format((float) $sku->profit, 0, ',', '.') }} ₫</div>
                  <div class="profit-signal-meta">SL ròng {{ number_format((float) $sku->net_quantity, 0, ',', '.') }}</div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-muted">Chưa có mã hàng đang lãi.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Mã bán càng nhiều càng lỗ</h5>
        <span class="badge bg-label-danger">{{ number_format($selectedPeriod->skuSummaries->where('status', 'loss')->count(), 0, ',', '.') }} mã</span>
      </div>
      <div class="card-body">
        <div class="profit-signal-list" id="loss-list">
          @forelse($lossSkus as $sku)
            <div class="profit-signal-row">
              <div class="d-flex justify-content-between gap-3">
                <div>
                  <div class="profit-signal-sku">{{ $sku->fob_sku ?: $sku->seller_sku }}</div>
                  <div class="profit-signal-meta">{{ $sku->seller_sku }}</div>
                </div>
                <div class="text-end">
                  <div class="text-danger fw-semibold profit-number">{{ number_format((float) $sku->profit, 0, ',', '.') }} ₫</div>
                  <div class="profit-signal-meta">SL ròng {{ number_format((float) $sku->net_quantity, 0, ',', '.') }}</div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-muted">Chưa có mã bán lỗ.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <h5 class="mb-0">Chi tiết theo mã hàng</h5>
      <div class="text-muted">{{ number_format($selectedPeriod->sku_count, 0, ',', '.') }} SKU</div>
    </div>
    <div class="profit-table-wrap">
      <table class="table profit-sku-table mb-0">
        <thead>
          <tr>
            <th>Mã FOB</th>
            <th>Seller SKU</th>
            <th>Sản phẩm</th>
            <th class="text-end">SL bán ròng</th>
            <th class="text-end">{{ $activeTab === 'total' ? 'DT SKU đã cộng gộp' : 'DT từ file tất cả đơn hàng/SKU' }}</th>
            <th class="text-end">{{ $activeTab === 'total' ? 'Chênh lệch tổng phân bổ' : 'Chênh lệch chia về từng mã' }}</th>
            <th class="text-end">DT sau khi chia chênh lệch</th>
            <th class="text-end">Giá vốn/sp</th>
            <th class="text-end">Tổng giá vốn</th>
            <th class="text-end">Phí phân bổ</th>
            <th class="text-end">QC phân bổ</th>
            <th class="text-end">Lãi/lỗ</th>
            <th class="text-end">Lãi/sp</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          @forelse($selectedPeriod->skuSummaries as $sku)
            <tr>
              <td>{{ $sku->fob_sku ?: '-' }}</td>
              <td class="profit-sku-name">{{ $sku->seller_sku }}</td>
              <td><div class="profit-product-name" title="{{ $sku->product_name }}">{{ $sku->product_name ?: '-' }}</div></td>
              <td class="text-end profit-number">{{ number_format((float) $sku->net_quantity, 0, ',', '.') }}</td>
              <td class="text-end profit-number">{{ number_format((float) ($sku->original_revenue ?: $sku->revenue), 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number {{ (float) ($sku->allocated_revenue_adjustment ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) ($sku->allocated_revenue_adjustment ?? 0), 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number">{{ number_format((float) $sku->revenue, 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number">{{ number_format((float) $sku->unit_cost, 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number">{{ number_format((float) $sku->cogs, 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number">{{ number_format((float) $sku->allocated_fees, 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number">{{ number_format((float) $sku->allocated_ad_cost, 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number fw-semibold {{ $sku->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sku->profit, 0, ',', '.') }} ₫</td>
              <td class="text-end profit-number {{ $sku->profit_per_unit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $sku->profit_per_unit, 0, ',', '.') }} ₫</td>
              <td><span class="badge {{ $sku->status === 'profit' ? 'bg-label-success' : 'bg-label-danger' }}">{{ $sku->status === 'profit' ? 'Đang lãi' : 'Đang lỗ' }}</span></td>
            </tr>
          @empty
            <tr><td colspan="14" class="text-center py-4">Chưa có dữ liệu SKU.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif
@else
  <div class="card mb-4">
    <div class="card-body text-center py-5">
      <h5 class="mb-2">Chưa có kỳ phân tích nào</h5>
      <div class="text-muted mb-3">Upload 3 file và nhập chi phí QC mỗi đơn hàng để tạo bộ thống kê đầu tiên.</div>
      @if (hasPermission('PHAN_TICH_LAI_LO_CREATE'))
        <a href="{{ route('phan-tich-lai-lo.create-marketplace', 'tiktok') }}" class="btn btn-outline-primary me-2">Nhập dữ liệu TikTok</a>
        <a href="{{ route('phan-tich-lai-lo.create-marketplace', 'shopee') }}" class="btn btn-outline-primary">Nhập dữ liệu Shopee</a>
      @endif
    </div>
  </div>
@endif

<div class="card">
  <div class="card-header"><h5 class="mb-0">Các tháng đã chốt{{ $activeTab !== 'total' ? ' - '.$selectedMarketplaceLabel : '' }}</h5></div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Tháng</th>
          <th>Nền tảng</th>
          <th>Khoảng ngày</th>
          <th class="text-end">Doanh thu</th>
          <th class="text-end">Chi phí</th>
          <th class="text-end">Lãi/lỗ</th>
          <th class="text-end">SKU</th>
          <th>Chốt bởi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($listedPeriods as $period)
          <tr>
            <td class="fw-semibold">{{ $period->label }}</td>
            <td><span class="badge bg-label-{{ $period->marketplace === 'shopee' ? 'warning' : 'info' }}">{{ $period->marketplace_label }}</span></td>
            <td>
              @if($period->period_start && $period->period_end)
                {{ $period->period_start->format('d/m/Y') }} - {{ $period->period_end->format('d/m/Y') }}
              @else
                -
              @endif
            </td>
            <td class="text-end profit-number">{{ number_format((float) $period->total_revenue, 0, ',', '.') }} ₫</td>
            <td class="text-end profit-number">{{ number_format((float) $period->total_cost, 0, ',', '.') }} ₫</td>
            <td class="text-end profit-number fw-semibold {{ $period->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $period->profit, 0, ',', '.') }} ₫</td>
            <td class="text-end profit-number">{{ number_format($period->sku_count, 0, ',', '.') }}</td>
            <td>{{ $period->confirmedBy?->name ?: '-' }}</td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-2">
                @if (hasPermission('PHAN_TICH_LAI_LO_EDIT'))
                  <a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('phan-tich-lai-lo.edit', $period) }}" aria-label="Sửa {{ $period->label }}">
                    <i class="icon-base bx bx-edit"></i>
                  </a>
                @endif
                @if (hasPermission('PHAN_TICH_LAI_LO_DELETE'))
                  <form method="POST" action="{{ route('phan-tich-lai-lo.destroy', $period) }}" onsubmit="return confirm('Xóa dữ liệu {{ $period->label }}? Thao tác này chỉ xóa bộ thống kê của tháng, không xóa file gốc.')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-icon btn-outline-danger" aria-label="Xóa {{ $period->label }}">
                      <i class="icon-base bx bx-trash"></i>
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center py-4">Chưa có dữ liệu.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($activeTab === 'total' && $periods->hasPages())<div class="card-footer">{{ $periods->links() }}</div>@endif
</div>

@endsection
