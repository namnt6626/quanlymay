@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard')

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const monthInput = document.getElementById('time_month');
      const weekSelect = document.getElementById('time_week');

      function formatDayMonth(date) {
        return new Intl.DateTimeFormat('vi-VN', {
          day: '2-digit',
          month: '2-digit',
        }).format(date);
      }

      function rebuildMonthWeeks() {
        if (!monthInput?.value || !weekSelect) {
          return;
        }

        const [year, month] = monthInput.value.split('-').map(Number);
        const daysInMonth = new Date(year, month, 0).getDate();
        const today = new Date();
        const isCurrentMonth = year === today.getFullYear() && month === today.getMonth() + 1;
        const lastAvailableDay = isCurrentMonth ? today.getDate() : daysInMonth;
        const maxWeek = Math.ceil(lastAvailableDay / 7);
        const previousWeek = Math.min(Number(weekSelect.value || 1), maxWeek);

        weekSelect.innerHTML = '';

        for (let week = 1; week <= maxWeek; week++) {
          const fromDay = ((week - 1) * 7) + 1;
          const toDay = Math.min(fromDay + 6, lastAvailableDay);
          const from = new Date(year, month - 1, fromDay);
          const to = new Date(year, month - 1, toDay);
          const option = document.createElement('option');

          option.value = String(week);
          option.textContent = `Tuần ${week} (${formatDayMonth(from)} - ${formatDayMonth(to)})`;
          option.selected = week === previousWeek;
          weekSelect.appendChild(option);
        }
      }

      monthInput?.addEventListener('change', rebuildMonthWeeks);

      const onlineTrendEl = document.querySelector('#dashboard-online-sales-trend');
      const onlineTopEl = document.querySelector('#dashboard-online-top-products');
      const currency = value => new Intl.NumberFormat('vi-VN').format(value) + ' ₫';

      if (@json(request('tab') === 'online') && onlineTrendEl && window.ApexCharts) {
        new ApexCharts(onlineTrendEl, {
          chart: { type: 'line', height: 320, toolbar: { show: false } },
          stroke: { curve: 'smooth', width: [3, 3] },
          series: [
            { name: 'Tổng tiền', type: 'area', data: @json($onlineTrend->pluck('tong_tien')->map(fn($v) => (float) $v)->values()) },
            { name: 'Số lượng', type: 'line', data: @json($onlineTrend->pluck('so_luong')->map(fn($v) => (float) $v)->values()) }
          ],
          xaxis: { categories: @json($onlineTrend->pluck('ngay')->map(fn($v) => \Illuminate\Support\Carbon::parse($v)->format('d/m'))->values()) },
          yaxis: [
            { labels: { formatter: value => new Intl.NumberFormat('vi-VN', { notation: 'compact' }).format(value) } },
            { opposite: true, labels: { formatter: value => Math.round(value) } }
          ],
          tooltip: { shared: true, y: [{ formatter: currency }, { formatter: value => new Intl.NumberFormat('vi-VN').format(value) }] },
          colors: ['#696cff', '#03c3ec']
        }).render();
      }

      if (@json(request('tab') === 'online') && onlineTopEl && window.ApexCharts) {
        new ApexCharts(onlineTopEl, {
          chart: { type: 'bar', height: 320, toolbar: { show: false } },
          plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
          series: [{ name: 'Số lượng', data: @json($onlineTopProducts->pluck('so_luong')->map(fn($v) => (float) $v)->reverse()->values()) }],
          xaxis: { categories: @json($onlineTopProducts->pluck('ten_san_pham')->map(fn($v) => \Illuminate\Support\Str::limit($v, 28))->reverse()->values()) },
          colors: ['#71dd37'],
          dataLabels: { enabled: false }
        }).render();
      }
    });
  </script>
@endsection

@section('page-style')
  <style>
    .dashboard-section > .card-body.border-top {
      padding-top: 1.75rem;
    }

    .dashboard-report-filter .form-control,
    .dashboard-report-filter .form-select,
    .dashboard-report-filter .btn {
      min-height: 38px;
    }

    .dashboard-online-filter {
      padding: 1rem;
      border: 1px solid var(--bs-border-color);
      border-radius: .5rem;
      background: var(--bs-gray-100);
    }

    .dashboard-online-filter-row {
      display: grid;
      gap: 1rem;
    }

    .dashboard-online-filter-row + .dashboard-online-filter-row {
      margin-top: 1rem;
    }

    .dashboard-online-filter-primary {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .dashboard-online-filter-secondary {
      grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) minmax(120px, .8fr) auto;
      align-items: end;
    }

    .dashboard-online-filter .form-label {
      margin-bottom: .35rem;
      color: var(--bs-secondary-color);
      font-size: .75rem;
      font-weight: 700;
      letter-spacing: .01em;
      text-transform: uppercase;
    }

    .dashboard-online-filter .form-control,
    .dashboard-online-filter .form-select {
      min-height: 42px;
      background-color: var(--bs-body-bg);
    }

    .dashboard-online-filter .online-filter-actions {
      height: 100%;
      min-height: 42px;
      align-items: end;
    }

    .dashboard-online-filter .online-filter-actions .btn {
      min-height: 42px;
    }

    .dashboard-online-filter .online-filter-actions .btn-primary {
      min-width: 96px;
    }

    @media (max-width: 1199.98px) {
      .dashboard-online-filter-primary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .dashboard-online-filter-secondary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .dashboard-online-filter-secondary .online-filter-actions {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 575.98px) {
      .dashboard-online-filter-primary,
      .dashboard-online-filter-secondary {
        grid-template-columns: 1fr;
      }
    }

    .dashboard-report-filter-actions {
      display: flex;
      gap: .5rem;
    }

    .dashboard-report-filter-actions .btn {
      flex: 1 1 auto;
      white-space: nowrap;
    }

    .dashboard-report-kpi {
      border: 1px solid var(--bs-border-color);
    }

    .dashboard-report-kpi .kpi-icon {
      width: 42px;
      height: 42px;
      display: grid;
      place-items: center;
      border-radius: 12px;
      flex: 0 0 42px;
    }

    .dashboard-report-scroll {
      overflow-x: auto;
      scrollbar-gutter: stable;
    }

    .dashboard-online-table {
      min-width: 1200px;
    }

    .dashboard-online-daily-table {
      min-width: 760px;
    }

    .dashboard-order-table {
      min-width: 1320px;
    }

    .dashboard-product-name {
      max-width: 380px;
      white-space: normal;
      line-height: 1.4;
    }

    @media (max-width: 575.98px) {
      .dashboard-section {
        margin-bottom: 1rem !important;
      }

      .dashboard-section > .card-header {
        padding: 0.875rem 1rem;
      }

      .dashboard-section > .card-header h5 {
        font-size: 1rem;
      }

      .dashboard-section > .card-body {
        padding: 1rem;
      }

      .dashboard-section > .card-body.border-top {
        padding-top: 1.25rem;
      }

      .dashboard-section .form-label {
        margin-bottom: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
      }

      .dashboard-filter-actions {
        width: 100%;
      }

      .dashboard-filter-actions .btn {
        flex: 1 1 0;
        justify-content: center;
        white-space: nowrap;
      }

      .dashboard-report-filter-actions {
        width: 100%;
      }

      .dashboard-online-filter {
        padding: .875rem;
      }

      .dashboard-stat-card .card-body {
        display: block !important;
        min-height: 118px;
        padding: 0.875rem;
        text-align: left !important;
      }

      .dashboard-stat-card .avatar {
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
      }

      .dashboard-stat-card .icon-lg {
        font-size: 1.15rem !important;
      }

      .dashboard-stat-card .text-muted.small {
        line-height: 1.2;
        min-height: 2.05em;
      }

      .dashboard-stat-card .h4 {
        margin-top: 0.25rem;
        font-size: clamp(1.2rem, 6vw, 1.65rem);
        line-height: 1.05;
        white-space: nowrap;
        letter-spacing: 0;
      }

      .dashboard-stat-card .card-body .avatar,
      .dashboard-stat-card .card-body.text-center .avatar {
        margin: 0 0 0.65rem !important;
      }

      .dashboard-stat-card .min-w-0 {
        min-width: 0;
        flex: 1 1 auto;
      }

      .dashboard-daily-table-wrap {
        overflow-x: visible;
      }

      .dashboard-daily-table thead {
        display: none;
      }

      .dashboard-daily-table,
      .dashboard-daily-table tbody,
      .dashboard-daily-table tr,
      .dashboard-daily-table td {
        display: block;
        width: 100%;
      }

      .dashboard-daily-table tbody tr:not(.dashboard-empty-row) {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem 0.65rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        margin: 0 1rem 0.875rem;
        padding: 0.75rem;
        background-color: var(--bs-card-bg, #fff);
      }

      .dashboard-daily-table tbody tr:not(.dashboard-empty-row) td {
        border: 0;
        border-radius: 0.375rem;
        padding: 0.5rem;
        background-color: #fafbfc;
        text-align: left !important;
        white-space: normal;
      }

      .dashboard-daily-table tbody tr:not(.dashboard-empty-row) td::before {
        display: block;
        content: attr(data-label);
        color: var(--bs-secondary-color);
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.15rem;
        opacity: 0.85;
      }

      .dashboard-daily-table .dashboard-daily-date {
        grid-column: 1 / -1;
        font-weight: 700;
      }
    }
  </style>
@endsection

@php
  $formatNumber = $formatNumber ?? function ($value) {
      if ($value === null || $value === '') {
          return '-';
      }

      $number = (float) $value;

      if (floor($number) == $number) {
          return number_format($number, 0, ',', '.');
      }

      return rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');
  };

  $quickKeys = [
      'quick_ma_don',
      'quick_ma_kh',
      'quick_mat_hang_id',
      'quick_mau_id',
      'quick_size_id',
      'quick_kenh_ban',
      'quick_ngay_nhan_tu',
      'quick_ngay_nhan_den',
      'quick_han_giao_tu',
      'quick_han_giao_den',
  ];
  $timeKeys = [
      'time_month',
      'time_week',
      'time_ma_don',
      'time_ma_kh',
      'time_mat_hang_id',
      'time_mau_id',
      'time_size_id',
  ];
  $dailyKeys = ['daily_date_from', 'daily_date_to', 'daily_per_page'];
  $onlineKeys = ['online_tu_ngay', 'online_den_ngay', 'online_ma_hang', 'online_mau', 'online_size', 'online_kenh_ban', 'online_per_page', 'online_daily_page', 'online_page'];
  $orderKeys = [
      'order_q',
      'order_ma_don',
      'order_ma_kh',
      'order_mat_hang_id',
      'order_mau_id',
      'order_size_id',
      'order_ngay_nhan_tu',
      'order_ngay_nhan_den',
      'order_han_giao_tu',
      'order_han_giao_den',
      'order_per_page',
      'order_page',
  ];
  $activeDashboardTab = in_array(request('tab'), ['overview', 'online', 'orders'], true) ? request('tab') : 'overview';
  $dashboardTabs = [
      ['key' => 'overview', 'label' => 'Dashboard hiện tại', 'icon' => 'bx-home-smile'],
      ['key' => 'online', 'label' => 'Bán hàng online', 'icon' => 'bx-line-chart'],
      ['key' => 'orders', 'label' => 'Tổng hợp đơn hàng', 'icon' => 'bx-receipt'],
  ];
  $dashboardTabUrl = fn (string $tab) => route('dashboard-analytics', array_merge(request()->query(), ['tab' => $tab]));

  $quickPreserved = request()->only([...$timeKeys, ...$dailyKeys, ...$onlineKeys, ...$orderKeys]);
  $timePreserved = request()->only([...$quickKeys, ...$dailyKeys, ...$onlineKeys, ...$orderKeys]);
  $dailyPreserved = request()->only([...$quickKeys, ...$timeKeys, ...$onlineKeys, ...$orderKeys]);
  $onlinePreserved = request()->only([...$quickKeys, ...$timeKeys, ...$dailyKeys, ...$orderKeys]);
  $orderPreserved = request()->only([...$quickKeys, ...$timeKeys, ...$dailyKeys, ...$onlineKeys]);

  $quickResetUrl = route('dashboard-analytics', array_merge(request()->except($quickKeys), ['tab' => 'overview']));
  $timeResetUrl = route('dashboard-analytics', array_merge(request()->except($timeKeys), ['tab' => 'overview']));
  $dailyResetUrl = route('dashboard-analytics', array_merge(request()->except($dailyKeys), ['tab' => 'overview']));
  $onlineResetUrl = route('dashboard-analytics', array_merge(request()->except($onlineKeys), ['tab' => 'online']));
  $orderResetUrl = route('dashboard-analytics', array_merge(request()->except($orderKeys), ['tab' => 'orders']));

  $quickCards = [
      ['label' => 'Tổng SL đặt', 'value' => $quickSummary['tong_sl_dat'] ?? 0, 'icon' => 'bx-receipt', 'class' => 'primary'],
      ['label' => 'Đã cắt', 'value' => $quickSummary['da_cat'] ?? 0, 'icon' => 'bx-cut', 'class' => 'info'],
      ['label' => 'Đã giao may', 'value' => $quickSummary['da_giao_may'] ?? 0, 'icon' => 'bx-send', 'class' => 'success'],
      ['label' => 'QC đạt', 'value' => $quickSummary['qc_dat'] ?? 0, 'icon' => 'bx-check-circle', 'class' => 'success'],
      ['label' => 'QC lỗi', 'value' => $quickSummary['qc_loi'] ?? 0, 'icon' => 'bx-error-circle', 'class' => 'warning'],
      ['label' => 'Nhập kho', 'value' => $quickSummary['nhap_kho'] ?? 0, 'icon' => 'bx-archive-in', 'class' => 'info'],
      ['label' => 'Đã xuất', 'value' => $quickSummary['da_xuat'] ?? 0, 'icon' => 'bx-archive-out', 'class' => 'secondary'],
      ['label' => 'Tồn tổng', 'value' => $quickSummary['ton_kho'] ?? 0, 'icon' => 'bx-package', 'class' => 'primary'],
      ['label' => 'Còn cắt', 'value' => $quickSummary['con_cat'] ?? 0, 'icon' => 'bx-time-five', 'class' => 'warning'],
      ['label' => 'Còn giao', 'value' => $quickSummary['con_giao'] ?? 0, 'icon' => 'bx-transfer', 'class' => 'warning'],
      ['label' => 'Dòng thiếu cắt', 'value' => $quickSummary['dong_thieu_cat'] ?? 0, 'icon' => 'bx-list-minus', 'class' => 'danger'],
      ['label' => 'Dòng thiếu hàng kho', 'value' => $quickSummary['dong_thieu_hang_kho'] ?? 0, 'icon' => 'bx-package', 'class' => 'danger'],
  ];

  $timeCards = [
      ['label' => 'Đã cắt', 'value' => $timeProductionSummary['da_cat'] ?? 0, 'icon' => 'bx-cut', 'class' => 'info'],
      ['label' => 'Đã giao may', 'value' => $timeProductionSummary['da_giao_may'] ?? 0, 'icon' => 'bx-send', 'class' => 'success'],
      ['label' => 'QC đạt', 'value' => $timeProductionSummary['qc_dat'] ?? 0, 'icon' => 'bx-check-circle', 'class' => 'success'],
      ['label' => 'QC lỗi', 'value' => $timeProductionSummary['qc_loi'] ?? 0, 'icon' => 'bx-error-circle', 'class' => 'warning'],
      ['label' => 'Nhập kho', 'value' => $timeProductionSummary['nhap_kho'] ?? 0, 'icon' => 'bx-archive-in', 'class' => 'primary'],
      ['label' => 'Đã xuất', 'value' => $timeProductionSummary['da_xuat'] ?? 0, 'icon' => 'bx-archive-out', 'class' => 'secondary'],
      ['label' => 'Tồn tổng cuối kỳ', 'value' => $timeProductionSummary['ton_kho'] ?? 0, 'icon' => 'bx-package', 'class' => 'primary'],
  ];

  $todayCards = [
      ['label' => 'Hôm nay cắt', 'value' => $todayProduction['cat'] ?? 0, 'icon' => 'bx-cut', 'class' => 'info'],
      ['label' => 'Hôm nay giao may', 'value' => $todayProduction['giao_may'] ?? 0, 'icon' => 'bx-send', 'class' => 'success'],
      ['label' => 'Hôm nay QC đạt', 'value' => $todayProduction['qc_dat'] ?? 0, 'icon' => 'bx-check-circle', 'class' => 'success'],
      ['label' => 'Hôm nay QC lỗi', 'value' => $todayProduction['qc_loi'] ?? 0, 'icon' => 'bx-error-circle', 'class' => 'warning'],
      ['label' => 'Hôm nay nhập kho', 'value' => $todayProduction['nhap_kho'] ?? 0, 'icon' => 'bx-archive-in', 'class' => 'primary'],
      ['label' => 'Hôm nay xuất hàng', 'value' => $todayProduction['xuat_hang'] ?? 0, 'icon' => 'bx-archive-out', 'class' => 'secondary'],
  ];

  $onlineQuantity = (float) ($onlineTotals->tong_so_luong ?? 0);
  $onlineRevenue = (float) ($onlineTotals->tong_tien_ban_hang ?? 0);
  $onlineSalesDays = (int) ($onlineTotals->so_ngay_ban ?? 0);
  $onlineAverage = $onlineQuantity > 0 ? $onlineRevenue / $onlineQuantity : 0;
  $onlineDailyAverage = $onlineSalesDays > 0 ? $onlineRevenue / $onlineSalesDays : 0;
@endphp

@section('content')
  <ul class="nav nav-tabs flex-nowrap overflow-auto mb-4" role="tablist">
    @foreach ($dashboardTabs as $tab)
      <li class="nav-item" role="presentation">
        <a href="{{ $dashboardTabUrl($tab['key']) }}"
          class="nav-link text-nowrap {{ $activeDashboardTab === $tab['key'] ? 'active' : '' }}">
          <i class="icon-base bx {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
        </a>
      </li>
    @endforeach
  </ul>

  <div class="tab-content p-0 bg-transparent shadow-none">
    <div class="tab-pane fade {{ $activeDashboardTab === 'overview' ? 'show active' : '' }}">
  <div class="card mb-5 dashboard-section">
    <div class="card-header">
      <h5 class="mb-0">Bảng tổng nhanh</h5>
    </div>
    <div class="card-body border-top">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="overview">
        @foreach ($quickPreserved as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_ma_don">Mã đơn</label>
          <input type="text" class="form-control" id="quick_ma_don" name="quick_ma_don"
            value="{{ $quickFilters['ma_don'] ?? '' }}" placeholder="Mã đơn">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_ma_kh">Mã KH</label>
          <input type="text" class="form-control" id="quick_ma_kh" name="quick_ma_kh"
            value="{{ $quickFilters['ma_kh'] ?? '' }}" placeholder="Mã KH">
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <label class="form-label" for="quick_mat_hang_id">Mã hàng</label>
          <select class="form-select" id="quick_mat_hang_id" name="quick_mat_hang_id">
            <option value="">Tất cả</option>
            @foreach ($matHangs as $matHang)
              <option value="{{ $matHang->id }}" @selected((int) ($quickFilters['mat_hang_id'] ?? 0) === (int) $matHang->id)>
                {{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_mau_id">Màu</label>
          <select class="form-select" id="quick_mau_id" name="quick_mau_id">
            <option value="">Tất cả</option>
            @foreach ($maus as $mau)
              <option value="{{ $mau->id }}" @selected((int) ($quickFilters['mau_id'] ?? 0) === (int) $mau->id)>
                {{ $mau->ten_mau }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-1">
          <label class="form-label" for="quick_size_id">Size</label>
          <select class="form-select" id="quick_size_id" name="quick_size_id">
            <option value="">Tất cả</option>
            @foreach ($sizes as $size)
              <option value="{{ $size->id }}" @selected((int) ($quickFilters['size_id'] ?? 0) === (int) $size->id)>
                {{ $size->ten_size }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_kenh_ban">Kênh bán</label>
          <select class="form-select" id="quick_kenh_ban" name="quick_kenh_ban">
            <option value="">Tất cả</option>
            @foreach ($kenhBans as $kenhBan)
              <option value="{{ $kenhBan }}" @selected(($quickFilters['kenh_ban'] ?? '') === $kenhBan)>
                {{ $kenhBan }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_ngay_nhan_tu">Ngày nhận từ</label>
          <input type="date" class="form-control" id="quick_ngay_nhan_tu" name="quick_ngay_nhan_tu"
            value="{{ $quickFilters['ngay_nhan_tu'] ?? '' }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_ngay_nhan_den">Ngày nhận đến</label>
          <input type="date" class="form-control" id="quick_ngay_nhan_den" name="quick_ngay_nhan_den"
            value="{{ $quickFilters['ngay_nhan_den'] ?? '' }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_han_giao_tu">Hạn giao từ</label>
          <input type="date" class="form-control" id="quick_han_giao_tu" name="quick_han_giao_tu"
            value="{{ $quickFilters['han_giao_tu'] ?? '' }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="quick_han_giao_den">Hạn giao đến</label>
          <input type="date" class="form-control" id="quick_han_giao_den" name="quick_han_giao_den"
            value="{{ $quickFilters['han_giao_den'] ?? '' }}">
        </div>
        <div class="col-12 col-xl-4">
          <div class="d-flex gap-2 dashboard-filter-actions">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-filter-alt me-1"></i> Lọc
            </button>
            <a href="{{ $quickResetUrl }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>

      <div class="row g-4 mt-1">
        @foreach ($quickCards as $card)
          <div class="col-6 col-sm-6 col-xl-3">
            <div class="card h-100 shadow-none border dashboard-stat-card">
              <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar rounded bg-label-{{ $card['class'] }}">
                  <i class="icon-base bx {{ $card['icon'] }} icon-lg"></i>
                </span>
                <div class="min-w-0">
                  <div class="text-muted small">{{ $card['label'] }}</div>
                  <div class="h4 mb-0">{{ $formatNumber($card['value']) }}</div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card mb-5 dashboard-section">
    <div class="card-header">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0">Tổng sản lượng theo thời gian</h5>
        <span class="badge bg-label-primary">
          {{ \Carbon\Carbon::parse($timeProductionSummary['date_from'])->format('d/m/Y') }} -
          {{ \Carbon\Carbon::parse($timeProductionSummary['date_to'])->format('d/m/Y') }}
        </span>
      </div>
    </div>
    <div class="card-body border-top">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="overview">
        @foreach ($timePreserved as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="time_month">Tháng</label>
          <input type="month" class="form-control" id="time_month" name="time_month"
            value="{{ $timeFilters['month'] }}" max="{{ now()->format('Y-m') }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="time_week">Tuần trong tháng</label>
          <select class="form-select" id="time_week" name="time_week">
            @for ($week = 1; $week <= $maxWeekOfMonth; $week++)
              @php
                $weekFrom = \Carbon\Carbon::createFromFormat('Y-m', $timeFilters['month'])
                    ->startOfMonth()
                    ->addDays(($week - 1) * 7);
                $weekTo = $weekFrom->copy()->addDays(6)->min($weekFrom->copy()->endOfMonth());
                if ($weekFrom->isSameMonth(now())) {
                    $weekTo = $weekTo->min(now()->startOfDay());
                }
              @endphp
              <option value="{{ $week }}" @selected((int) $timeFilters['week'] === $week)>
                Tuần {{ $week }} ({{ $weekFrom->format('d/m') }} - {{ $weekTo->format('d/m') }})
              </option>
            @endfor
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="time_ma_don">Mã đơn</label>
          <input type="text" class="form-control" id="time_ma_don" name="time_ma_don"
            value="{{ $timeFilters['ma_don'] ?? '' }}" placeholder="Mã đơn">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="time_ma_kh">Mã KH</label>
          <input type="text" class="form-control" id="time_ma_kh" name="time_ma_kh"
            value="{{ $timeFilters['ma_kh'] ?? '' }}" placeholder="Mã KH">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="time_mat_hang_id">Mã hàng</label>
          <select class="form-select" id="time_mat_hang_id" name="time_mat_hang_id">
            <option value="">Tất cả</option>
            @foreach ($matHangs as $matHang)
              <option value="{{ $matHang->id }}" @selected((int) ($timeFilters['mat_hang_id'] ?? 0) === (int) $matHang->id)>
                {{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-1">
          <label class="form-label" for="time_mau_id">Màu</label>
          <select class="form-select" id="time_mau_id" name="time_mau_id">
            <option value="">Tất cả</option>
            @foreach ($maus as $mau)
              <option value="{{ $mau->id }}" @selected((int) ($timeFilters['mau_id'] ?? 0) === (int) $mau->id)>
                {{ $mau->ten_mau }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-1">
          <label class="form-label" for="time_size_id">Size</label>
          <select class="form-select" id="time_size_id" name="time_size_id">
            <option value="">Tất cả</option>
            @foreach ($sizes as $size)
              <option value="{{ $size->id }}" @selected((int) ($timeFilters['size_id'] ?? 0) === (int) $size->id)>
                {{ $size->ten_size }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12">
          <div class="d-flex gap-2 dashboard-filter-actions">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-filter-alt me-1"></i> Lọc
            </button>
            <a href="{{ $timeResetUrl }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>

      <div class="row g-4 mt-1">
        @foreach ($timeCards as $card)
          <div class="col-6 col-sm-6 col-lg-4 col-xxl">
            <div class="card h-100 shadow-none border dashboard-stat-card">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-3 rounded bg-label-{{ $card['class'] }}">
                  <i class="icon-base bx {{ $card['icon'] }} icon-lg"></i>
                </div>
                <div class="text-muted small">{{ $card['label'] }}</div>
                <div class="h4 mb-0">{{ $formatNumber($card['value']) }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card mb-5 dashboard-section">
    <div class="card-header">
      <h5 class="mb-0">Chỉ số hôm nay</h5>
    </div>
    <div class="card-body border-top">
      <div class="row g-4">
        @foreach ($todayCards as $card)
          <div class="col-6 col-sm-6 col-lg-4 col-xxl-2">
            <div class="card h-100 shadow-none border dashboard-stat-card">
              <div class="card-body text-center">
                <div class="avatar mx-auto mb-3 rounded bg-label-{{ $card['class'] }}">
                  <i class="icon-base bx {{ $card['icon'] }} icon-lg"></i>
                </div>
                <div class="text-muted small">{{ $card['label'] }}</div>
                <div class="h4 mb-0">{{ $formatNumber($card['value']) }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card mb-5 dashboard-section">
    <div class="card-header">
      <h5 class="mb-0">Sản lượng theo ngày</h5>
    </div>
    <div class="card-body border-top pb-0">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="overview">
        @foreach ($dailyPreserved as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="col-12 col-md-6 col-xl-3">
          <label class="form-label" for="daily_date_from">Sản lượng từ ngày</label>
          <input type="date" class="form-control" id="daily_date_from" name="daily_date_from"
            value="{{ $dailyFilters['date_from'] ?? '' }}">
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <label class="form-label" for="daily_date_to">Sản lượng đến ngày</label>
          <input type="date" class="form-control" id="daily_date_to" name="daily_date_to"
            value="{{ $dailyFilters['date_to'] ?? '' }}">
        </div>
        <div class="col-6 col-md-3 col-xl-2">
          <label class="form-label" for="daily_per_page">Hiển thị</label>
          <select class="form-select" id="daily_per_page" name="daily_per_page" onchange="this.form.submit()">
            @foreach (paginationPerPageOptions() as $option)
              <option value="{{ $option }}" @selected($dailyPerPage === $option)>{{ $option }} dòng</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-9 col-xl-4">
          <div class="d-flex gap-2 dashboard-filter-actions">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-filter-alt me-1"></i> Lọc
            </button>
            <a href="{{ $dailyResetUrl }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>
    </div>
    <div class="table-responsive mt-3 dashboard-daily-table-wrap">
      <table class="table align-middle dashboard-daily-table">
        <thead>
          <tr>
            <th>Ngày</th>
            <th class="text-end">Cắt</th>
            <th class="text-end">Giao may</th>
            <th class="text-end">QC đạt</th>
            <th class="text-end">QC lỗi</th>
            <th class="text-end">Nhập kho</th>
            <th class="text-end">Xuất hàng</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($dailyProduction as $row)
            <tr>
              <td class="dashboard-daily-date" data-label="Ngày">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
              <td class="text-end" data-label="Cắt">{{ $formatNumber($row['cat']) }}</td>
              <td class="text-end" data-label="Giao may">{{ $formatNumber($row['giao_may']) }}</td>
              <td class="text-end" data-label="QC đạt">{{ $formatNumber($row['qc_dat']) }}</td>
              <td class="text-end" data-label="QC lỗi">{{ $formatNumber($row['qc_loi']) }}</td>
              <td class="text-end" data-label="Nhập kho">{{ $formatNumber($row['nhap_kho']) }}</td>
              <td class="text-end" data-label="Xuất hàng">{{ $formatNumber($row['xuat_hang']) }}</td>
            </tr>
          @empty
            <tr class="dashboard-empty-row">
              <td colspan="7" class="text-center py-4">Chưa có dữ liệu sản lượng.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($dailyProduction->hasPages())
      <div class="card-footer">
        {{ $dailyProduction->links() }}
      </div>
    @endif
  </div>

    </div>

    <div class="tab-pane fade {{ $activeDashboardTab === 'online' ? 'show active' : '' }}">
  <div class="card mb-5 dashboard-section">
    <div class="card-header">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <h5 class="mb-0">Bán hàng online</h5>
          <div class="text-muted small mt-1">Tổng hợp số lượng và tổng tiền bán hàng từ file đơn hoàn thành.</div>
        </div>
        <span class="badge bg-label-primary">
          {{ \Carbon\Carbon::parse($onlineFilters['tu_ngay'])->format('d/m/Y') }} -
          {{ \Carbon\Carbon::parse($onlineFilters['den_ngay'])->format('d/m/Y') }}
        </span>
      </div>
    </div>
    <div class="card-body border-top">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="dashboard-report-filter dashboard-online-filter">
        <input type="hidden" name="tab" value="online">
        @foreach ($onlinePreserved as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="dashboard-online-filter-row dashboard-online-filter-primary">
          <div>
            <label class="form-label" for="online_tu_ngay">Từ ngày</label>
            <input type="date" class="form-control" id="online_tu_ngay" name="online_tu_ngay" value="{{ $onlineFilters['tu_ngay'] }}" required>
          </div>
          <div>
            <label class="form-label" for="online_den_ngay">Đến ngày</label>
            <input type="date" class="form-control" id="online_den_ngay" name="online_den_ngay" value="{{ $onlineFilters['den_ngay'] }}" required>
          </div>
          <div>
            <label class="form-label" for="online_kenh_ban">Kênh bán</label>
            <select class="form-select" id="online_kenh_ban" name="online_kenh_ban">
              <option value="">Tất cả</option>
              <option value="Tiktok" @selected(($onlineFilters['kenh_ban'] ?? '') === 'Tiktok')>Tiktok</option>
              <option value="Shopee" @selected(($onlineFilters['kenh_ban'] ?? '') === 'Shopee')>Shopee</option>
            </select>
          </div>
          <div>
            <label class="form-label" for="online_per_page">Hiển thị</label>
            <select class="form-select" id="online_per_page" name="online_per_page" onchange="this.form.submit()">
              @foreach (paginationPerPageOptions() as $option)
                <option value="{{ $option }}" @selected((int) $onlineFilters['per_page'] === $option)>{{ $option }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="dashboard-online-filter-row dashboard-online-filter-secondary">
          <div>
            <label class="form-label" for="online_ma_hang">Mã hàng</label>
            <input class="form-control" id="online_ma_hang" name="online_ma_hang" value="{{ $onlineFilters['ma_hang'] ?? '' }}" list="online_ma_hang_options" placeholder="Tất cả" autocomplete="off">
            <datalist id="online_ma_hang_options">
              @foreach ($onlineProducts as $product)
                <option value="{{ $product }}"></option>
              @endforeach
            </datalist>
          </div>
          <div>
            <label class="form-label" for="online_mau">Màu</label>
            <select class="form-select" id="online_mau" name="online_mau">
              <option value="">Tất cả</option>
              @foreach ($onlineMaus as $mau)
                <option value="{{ $mau }}" @selected(($onlineFilters['mau'] ?? '') === $mau)>{{ $mau }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="form-label" for="online_size">Size</label>
            <select class="form-select" id="online_size" name="online_size">
              <option value="">Tất cả</option>
              @foreach ($onlineSizes as $size)
                <option value="{{ $size }}" @selected(($onlineFilters['size'] ?? '') === $size)>{{ $size }}</option>
              @endforeach
            </select>
          </div>
          <div class="dashboard-report-filter-actions online-filter-actions">
            <a href="{{ $onlineResetUrl }}" class="btn btn-outline-secondary" title="Xóa lọc"><i class="icon-base bx bx-refresh"></i></a>
            <button type="submit" class="btn btn-primary"><i class="icon-base bx bx-filter-alt me-1"></i>Lọc</button>
          </div>
        </div>
      </form>

      <div class="row g-4 mt-1">
        <div class="col-sm-6 col-xl-3">
          <div class="card h-100 shadow-none dashboard-report-kpi">
            <div class="card-body d-flex gap-3">
              <div class="kpi-icon bg-label-primary"><i class="icon-base bx bx-package fs-4"></i></div>
              <div><div class="text-muted small">Sản phẩm đã bán</div><h4 class="mb-0">{{ number_format($onlineQuantity, 0, ',', '.') }}</h4></div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card h-100 shadow-none dashboard-report-kpi">
            <div class="card-body d-flex gap-3">
              <div class="kpi-icon bg-label-success"><i class="icon-base bx bx-wallet fs-4"></i></div>
              <div>
                <div class="text-muted small">Tổng tiền bán hàng</div>
                <h4 class="mb-0">{{ number_format($onlineRevenue, 0, ',', '.') }} ₫</h4>
                @if ($onlineRevenueChange !== null)
                  <small class="{{ $onlineRevenueChange >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $onlineRevenueChange >= 0 ? '+' : '' }}{{ number_format($onlineRevenueChange, 1, ',', '.') }}% so với kỳ trước
                  </small>
                @endif
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card h-100 shadow-none dashboard-report-kpi">
            <div class="card-body d-flex gap-3">
              <div class="kpi-icon bg-label-info"><i class="icon-base bx bx-calendar-check fs-4"></i></div>
              <div><div class="text-muted small">Tiền bán TB/ngày có bán</div><h4 class="mb-0">{{ number_format($onlineDailyAverage, 0, ',', '.') }} ₫</h4><small class="text-muted">{{ $onlineSalesDays }} ngày phát sinh</small></div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card h-100 shadow-none dashboard-report-kpi">
            <div class="card-body d-flex gap-3">
              <div class="kpi-icon bg-label-warning"><i class="icon-base bx bx-calculator fs-4"></i></div>
              <div><div class="text-muted small">Tiền bán / sản phẩm</div><h4 class="mb-0">{{ number_format($onlineAverage, 0, ',', '.') }} ₫</h4></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-1">
        <div class="col-xl-8">
          <div class="card h-100 shadow-none border">
            <div class="card-header"><h6 class="mb-0">Xu hướng bán hàng</h6></div>
            <div class="card-body"><div id="dashboard-online-sales-trend" style="min-height: 320px"></div></div>
          </div>
        </div>
        <div class="col-xl-4">
          <div class="card h-100 shadow-none border">
            <div class="card-header"><h6 class="mb-0">Top sản phẩm bán chạy</h6></div>
            <div class="card-body"><div id="dashboard-online-top-products" style="min-height: 320px"></div></div>
          </div>
        </div>
      </div>

      <div class="card shadow-none border mt-4">
        <div class="card-header">
          <h6 class="mb-0">Tổng hợp doanh thu từng ngày</h6>
        </div>
        <div class="dashboard-report-scroll">
          <table class="table align-middle dashboard-online-daily-table">
            <thead>
              <tr>
                <th>Ngày</th>
                <th class="text-end">Số lượng bán</th>
                <th class="text-end">Tổng tiền bán hàng</th>
                <th class="text-end">Tiền bán / sản phẩm</th>
                <th class="text-end">Tỷ trọng</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse ($onlineDailyRows as $dayRow)
                @php
                  $dayQuantity = (float) $dayRow->so_luong;
                  $dayRevenue = (float) $dayRow->tong_tien;
                @endphp
                <tr>
                  <td class="fw-semibold">{{ \Carbon\Carbon::parse($dayRow->ngay)->format('d/m/Y') }}</td>
                  <td class="text-end">{{ number_format($dayQuantity, 0, ',', '.') }}</td>
                  <td class="text-end fw-semibold">{{ number_format($dayRevenue, 0, ',', '.') }} ₫</td>
                  <td class="text-end">{{ number_format($dayQuantity > 0 ? $dayRevenue / $dayQuantity : 0, 0, ',', '.') }} ₫</td>
                  <td class="text-end">{{ $onlineRevenue > 0 ? number_format(($dayRevenue / $onlineRevenue) * 100, 1, ',', '.') : '0' }}%</td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center py-4">Không có dữ liệu doanh thu theo ngày trong kỳ đã chọn.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($onlineDailyRows->hasPages())
          <div class="card-footer">
            {{ $onlineDailyRows->links() }}
          </div>
        @endif
      </div>
    </div>

    <div class="dashboard-report-scroll">
      <table class="table align-middle dashboard-online-table">
        <thead>
          <tr>
            <th>Sản phẩm</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">Số lượng</th>
            <th class="text-end">Tổng tiền</th>
            <th class="text-end">Tỷ trọng</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($onlineRows as $row)
            <tr>
              <td><div class="dashboard-product-name fw-semibold" title="{{ $row->ten_san_pham }}">{{ $row->ten_san_pham }}</div></td>
              <td>{{ $row->mau ?: '-' }}</td>
              <td>{{ $row->size ?: '-' }}</td>
              <td class="text-end">{{ number_format((float) $row->so_luong, 0, ',', '.') }}</td>
              <td class="text-end fw-semibold">{{ number_format((float) $row->tong_tien, 0, ',', '.') }} ₫</td>
              <td class="text-end">{{ $onlineRevenue > 0 ? number_format(((float) $row->tong_tien / $onlineRevenue) * 100, 1, ',', '.') : '0' }}%</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center py-4">Không có dữ liệu bán hàng online trong kỳ đã chọn.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($onlineRows->hasPages())
      <div class="card-footer">{{ $onlineRows->links() }}</div>
    @endif
  </div>

    </div>

    <div class="tab-pane fade {{ $activeDashboardTab === 'orders' ? 'show active' : '' }}">
  <div class="card dashboard-section">
    <div class="card-header">
      <div>
        <h5 class="mb-0">Tổng hợp đơn hàng</h5>
        <div class="text-muted small mt-1">Theo dõi đặt hàng, sản xuất, tồn kho và phần còn phải xử lý.</div>
      </div>
    </div>
    <div class="card-body border-top pb-0">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="row g-3 align-items-end dashboard-report-filter">
        <input type="hidden" name="tab" value="orders">
        @foreach ($orderPreserved as $name => $value)
          <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="col-12 col-xl">
          <label class="form-label" for="order_q">Tìm kiếm</label>
          <input type="text" class="form-control" id="order_q" name="order_q" value="{{ $orderFilters['q'] }}" placeholder="Mã đơn, mã KH, mã hàng, màu hoặc size">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="order_ma_don">Mã đơn</label>
          <input type="text" class="form-control" id="order_ma_don" name="order_ma_don" value="{{ $orderFilters['ma_don'] }}" placeholder="Mã đơn">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="order_ma_kh">Mã KH</label>
          <input type="text" class="form-control" id="order_ma_kh" name="order_ma_kh" value="{{ $orderFilters['ma_kh'] }}" placeholder="Mã KH">
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="order_mat_hang_id">Mã hàng</label>
          <select class="form-select" id="order_mat_hang_id" name="order_mat_hang_id">
            <option value="">Tất cả</option>
            @foreach ($orderMatHangs as $matHang)
              <option value="{{ $matHang->id }}" @selected((int) ($orderFilters['mat_hang_id'] ?? 0) === (int) $matHang->id)>{{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="order_mau_id">Màu</label>
          <select class="form-select" id="order_mau_id" name="order_mau_id">
            <option value="">Tất cả</option>
            @foreach ($orderMaus as $mau)
              <option value="{{ $mau->id }}" @selected((int) ($orderFilters['mau_id'] ?? 0) === (int) $mau->id)>{{ $mau->ten_mau }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="order_size_id">Size</label>
          <select class="form-select" id="order_size_id" name="order_size_id">
            <option value="">Tất cả</option>
            @foreach ($orderSizes as $size)
              <option value="{{ $size->id }}" @selected((int) ($orderFilters['size_id'] ?? 0) === (int) $size->id)>{{ $size->ten_size }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="order_ngay_nhan_tu">Ngày nhận từ</label>
          <input type="date" class="form-control" id="order_ngay_nhan_tu" name="order_ngay_nhan_tu" value="{{ $orderFilters['ngay_nhan_tu'] }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="order_ngay_nhan_den">Ngày nhận đến</label>
          <input type="date" class="form-control" id="order_ngay_nhan_den" name="order_ngay_nhan_den" value="{{ $orderFilters['ngay_nhan_den'] }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="order_han_giao_tu">Hạn giao từ</label>
          <input type="date" class="form-control" id="order_han_giao_tu" name="order_han_giao_tu" value="{{ $orderFilters['han_giao_tu'] }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="order_han_giao_den">Hạn giao đến</label>
          <input type="date" class="form-control" id="order_han_giao_den" name="order_han_giao_den" value="{{ $orderFilters['han_giao_den'] }}">
        </div>
        <div class="col-6 col-xl-2">
          <label class="form-label" for="order_per_page">Hiển thị</label>
          <select class="form-select" id="order_per_page" name="order_per_page" onchange="this.form.submit()">
            @foreach (paginationPerPageOptions() as $option)
              <option value="{{ $option }}" @selected((int) $orderFilters['per_page'] === $option)>{{ $option }} dòng</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-xl-auto">
          <div class="dashboard-report-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm kiếm</button>
            <a href="{{ $orderResetUrl }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="dashboard-report-scroll mt-3">
      <table class="table align-middle dashboard-order-table">
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">SL đặt</th>
            <th class="text-end">Đã cắt</th>
            <th class="text-end">Đã giao may</th>
            <th class="text-end">QC đạt</th>
            <th class="text-end">QC lỗi</th>
            <th class="text-end">Nhập kho</th>
            <th class="text-end">Đã xuất</th>
            <th class="text-end">Tồn kho</th>
            <th class="text-end">Còn phải cắt</th>
            <th class="text-end">Còn phải giao</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($orderRows as $row)
            <tr>
              <td>{{ $row->ma_don }}</td>
              <td>{{ $row->ma_kh }}</td>
              <td>
                <strong>{{ $row->ma_hang }}</strong>
                <div class="text-muted small dashboard-product-name">{{ $row->ten_hang }}</div>
              </td>
              <td>{{ $row->ten_mau }}</td>
              <td>{{ $row->ten_size }}</td>
              <td class="text-end">{{ $formatNumber($row->so_luong_dat) }}</td>
              <td class="text-end">{{ $formatNumber($row->da_cat) }}</td>
              <td class="text-end">{{ $formatNumber($row->da_giao_may) }}</td>
              <td class="text-end">{{ $formatNumber($row->qc_dat) }}</td>
              <td class="text-end">{{ $formatNumber($row->qc_loi) }}</td>
              <td class="text-end">{{ $formatNumber($row->nhap_kho) }}</td>
              <td class="text-end">{{ $formatNumber($row->da_xuat) }}</td>
              <td class="text-end fw-semibold">{{ $formatNumber($row->ton_kho) }}</td>
              <td class="text-end">{{ $formatNumber($row->con_phai_cat) }}</td>
              <td class="text-end">{{ $formatNumber($row->con_phai_giao) }}</td>
            </tr>
          @empty
            <tr><td colspan="15" class="text-center py-4">Chưa có dữ liệu tổng hợp đơn hàng.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($orderRows->hasPages())
      <div class="card-footer">{{ $orderRows->links() }}</div>
    @endif
  </div>
    </div>
  </div>
@endsection
