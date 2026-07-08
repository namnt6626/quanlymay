@extends('layouts/contentNavbarLayout')
@section('title', 'Báo cáo bán hàng online')
@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection
@section('page-style')
<style>
  .online-report-kpi { border: 1px solid var(--bs-border-color); }
  .online-report-kpi .kpi-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; }
  .online-report-product { max-width: 380px; white-space: normal; line-height: 1.4; }
  .online-report-scroll { overflow-x: auto; scrollbar-gutter: stable; }
  .online-report-table { min-width: 1050px; }
  .online-report-filter .form-control,
  .online-report-filter .form-select,
  .online-report-filter .btn { min-height: 38px; }
  .online-report-filter-actions { display: flex; gap: .5rem; }
  .online-report-filter-actions .btn { flex: 1 1 auto; white-space: nowrap; }
  @media (min-width: 1200px) {
    .online-report-filter-actions .btn { padding-left: .75rem; padding-right: .75rem; }
  }
</style>
@endsection
@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mb-4">
  <div><h4 class="mb-1">Báo cáo bán hàng online</h4><div class="text-muted">Tổng hợp số lượng và tiền bán hàng online</div></div>
</div>

<div class="card mb-4"><div class="card-body">
  <form method="GET" class="row g-3 align-items-end online-report-filter">
    <div class="col-6 col-xl-2"><label class="form-label">Từ ngày</label><input type="date" name="tu_ngay" class="form-control" value="{{ $filters['tu_ngay'] }}" required></div>
    <div class="col-6 col-xl-2"><label class="form-label">Đến ngày</label><input type="date" name="den_ngay" class="form-control" value="{{ $filters['den_ngay'] }}" required></div>
    <div class="col-12 col-xl-5"><label class="form-label">Tìm kiếm</label><input name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Sản phẩm, màu, size"></div>
    @include('content.shared._per-page-select', ['perPageColumnClass' => 'col-6 col-xl-1'])
    <div class="col-12 col-xl-2 online-report-filter-actions"><a href="{{ route('bao-cao.ban-hang-online') }}" class="btn btn-outline-secondary" title="Đặt lại bộ lọc"><i class="icon-base bx bx-refresh"></i></a><button class="btn btn-primary"><i class="icon-base bx bx-filter-alt me-1"></i>Lọc</button></div>
  </form>
</div></div>

@php
  $quantity = (float)($totals->tong_so_luong ?? 0);
  $revenue = (float)($totals->tong_doanh_thu ?? 0);
  $salesDays = (int)($totals->so_ngay_ban ?? 0);
  $average = $quantity > 0 ? $revenue / $quantity : 0;
  $dailyAverage = $salesDays > 0 ? $revenue / $salesDays : 0;
@endphp
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3"><div class="card h-100 online-report-kpi"><div class="card-body d-flex gap-3"><div class="kpi-icon bg-label-primary"><i class="icon-base bx bx-package fs-4"></i></div><div><div class="text-muted small">Sản phẩm đã bán</div><h4 class="mb-0">{{ number_format($quantity, 0, ',', '.') }}</h4></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card h-100 online-report-kpi"><div class="card-body d-flex gap-3"><div class="kpi-icon bg-label-success"><i class="icon-base bx bx-wallet fs-4"></i></div><div><div class="text-muted small">Tổng tiền bán hàng</div><h4 class="mb-0">{{ number_format($revenue, 0, ',', '.') }} ₫</h4>@if($revenueChange !== null)<small class="{{ $revenueChange >= 0 ? 'text-success' : 'text-danger' }}">{{ $revenueChange >= 0 ? '+' : '' }}{{ number_format($revenueChange, 1, ',', '.') }}% so với kỳ trước</small>@endif</div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card h-100 online-report-kpi"><div class="card-body d-flex gap-3"><div class="kpi-icon bg-label-info"><i class="icon-base bx bx-calendar-check fs-4"></i></div><div><div class="text-muted small">Tiền bán TB/ngày có bán</div><h4 class="mb-0">{{ number_format($dailyAverage, 0, ',', '.') }} ₫</h4><small class="text-muted">{{ $salesDays }} ngày phát sinh</small></div></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="card h-100 online-report-kpi"><div class="card-body d-flex gap-3"><div class="kpi-icon bg-label-warning"><i class="icon-base bx bx-calculator fs-4"></i></div><div><div class="text-muted small">Tiền bán / sản phẩm</div><h4 class="mb-0">{{ number_format($average, 0, ',', '.') }} ₫</h4></div></div></div></div>
</div>

<div class="row g-4 mb-4">
  <div class="col-xl-8"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Xu hướng bán hàng</h5></div><div class="card-body"><div id="online-sales-trend" style="min-height:330px"></div></div></div></div>
  <div class="col-xl-4"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Top sản phẩm bán chạy</h5></div><div class="card-body"><div id="online-top-products" style="min-height:330px"></div></div></div></div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Phân tích sản phẩm · màu · size</h5></div>
  <div class="online-report-scroll"><table class="table align-middle online-report-table">
    <thead><tr><th>Sản phẩm</th><th>Màu</th><th>Size</th><th class="text-end">Số lượng</th><th class="text-end">Tổng tiền</th><th class="text-end">Tỷ trọng</th></tr></thead>
    <tbody>@forelse($rows as $row)<tr>
      <td><div class="online-report-product fw-semibold" title="{{ $row->ten_san_pham }}">{{ $row->ten_san_pham }}</div></td>
      <td>{{ $row->mau ?: '-' }}</td><td>{{ $row->size ?: '-' }}</td>
      <td class="text-end">{{ number_format((float)$row->so_luong, 0, ',', '.') }}</td><td class="text-end fw-semibold">{{ number_format((float)$row->doanh_thu, 0, ',', '.') }} ₫</td><td class="text-end">{{ $revenue > 0 ? number_format(((float)$row->doanh_thu / $revenue) * 100, 1, ',', '.') : '0' }}%</td>
    </tr>@empty<tr><td colspan="6" class="text-center py-4">Không có dữ liệu trong kỳ đã chọn.</td></tr>@endforelse</tbody>
  </table></div>
  @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection

@section('page-script')
@parent
<script>
document.addEventListener('DOMContentLoaded', () => {
  const currency = value => new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
  new ApexCharts(document.querySelector('#online-sales-trend'), {
    chart: { type: 'line', height: 330, toolbar: { show: false } }, stroke: { curve: 'smooth', width: [3, 3] },
    series: [
      { name: 'Tổng tiền', type: 'area', data: @json($trend->pluck('doanh_thu')->map(fn($v)=>(float)$v)->values()) },
      { name: 'Số lượng', type: 'line', data: @json($trend->pluck('so_luong')->map(fn($v)=>(float)$v)->values()) }
    ],
    xaxis: { categories: @json($trend->pluck('ngay')->map(fn($v)=>\Illuminate\Support\Carbon::parse($v)->format('d/m'))->values()) },
    yaxis: [{ labels: { formatter: value => new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) } }, { opposite: true, labels: { formatter: value => Math.round(value) } }],
    tooltip: { shared: true, y: [{ formatter: currency }, { formatter: value => new Intl.NumberFormat('vi-VN').format(value) }] }, colors: ['#696cff', '#03c3ec']
  }).render();
  new ApexCharts(document.querySelector('#online-top-products'), {
    chart: { type: 'bar', height: 330, toolbar: { show: false } }, plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
    series: [{ name: 'Số lượng', data: @json($topProducts->pluck('so_luong')->map(fn($v)=>(float)$v)->reverse()->values()) }],
    xaxis: { categories: @json($topProducts->pluck('ten_san_pham')->map(fn($v)=>\Illuminate\Support\Str::limit($v, 28))->reverse()->values()) },
    colors: ['#71dd37'], dataLabels: { enabled: false }
  }).render();
});
</script>
@endsection
