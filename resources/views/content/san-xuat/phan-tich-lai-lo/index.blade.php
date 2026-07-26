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
    <div class="text-muted">Mỗi tháng giữ một bộ dữ liệu chốt. Nhập lại cùng tháng sẽ thay thế dữ liệu tháng đó.</div>
  </div>
  @if (hasPermission('PHAN_TICH_LAI_LO_CREATE'))
    <a href="{{ route('phan-tich-lai-lo.create') }}" class="btn btn-primary">
      <i class="icon-base bx bx-upload me-1"></i>Nhập dữ liệu tháng
    </a>
  @endif
</div>

@if($selectedPeriod)
  <div class="card mb-4">
    <div class="card-body d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-end">
      <form method="GET" action="{{ route('phan-tich-lai-lo.index') }}" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-end">
        <div>
          <label class="form-label">Chọn tháng xem</label>
          <select class="form-select profit-period-select" name="period" onchange="this.form.submit()">
            <option value="all" @selected($isTotalView)>Tổng tất cả tháng</option>
            @foreach($periods as $periodOption)
              <option value="{{ $periodOption->id }}" @selected(!$isTotalView && $selectedPeriod->id === $periodOption->id)>
                {{ $periodOption->label }}
                @if($periodOption->period_start && $periodOption->period_end)
                  ({{ $periodOption->period_start->format('d/m/Y') }} - {{ $periodOption->period_end->format('d/m/Y') }})
                @endif
              </option>
            @endforeach
          </select>
        </div>
      </form>
      <div class="text-muted">Đang xem {{ $selectedPeriod->label }}</div>
    </div>
  </div>

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
        @if(!$isTotalView && hasPermission('PHAN_TICH_LAI_LO_EDIT'))
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
          <div class="profit-kpi-label">Tổng doanh thu</div>
          <div class="profit-kpi-value">{{ number_format((float) $selectedPeriod->total_revenue, 0, ',', '.') }} ₫</div>
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
          <div class="profit-kpi-label">QC hòa vốn</div>
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
            <th class="text-end">Doanh thu</th>
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
            <tr><td colspan="12" class="text-center py-4">Chưa có dữ liệu SKU.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@else
  <div class="card mb-4">
    <div class="card-body text-center py-5">
      <h5 class="mb-2">Chưa có kỳ phân tích nào</h5>
      <div class="text-muted mb-3">Upload 5 file để tạo bộ thống kê đầu tiên.</div>
      @if (hasPermission('PHAN_TICH_LAI_LO_CREATE'))
        <a href="{{ route('phan-tich-lai-lo.create') }}" class="btn btn-primary">Nhập dữ liệu tháng</a>
      @endif
    </div>
  </div>
@endif

<div class="card">
  <div class="card-header"><h5 class="mb-0">Các tháng đã chốt</h5></div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>Tháng</th>
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
        @forelse($periods as $period)
          <tr>
            <td class="fw-semibold">{{ $period->label }}</td>
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
          <tr><td colspan="8" class="text-center py-4">Chưa có dữ liệu.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($periods->hasPages())<div class="card-footer">{{ $periods->links() }}</div>@endif
</div>

@endsection
