@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard')

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
    });
  </script>
@endsection

@section('page-style')
  <style>
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
  $dailyKeys = ['daily_date_from', 'daily_date_to'];

  $quickPreserved = request()->only([...$timeKeys, ...$dailyKeys]);
  $timePreserved = request()->only([...$quickKeys, ...$dailyKeys]);
  $dailyPreserved = request()->only([...$quickKeys, ...$timeKeys]);

  $quickResetUrl = route('dashboard-analytics', request()->except($quickKeys));
  $timeResetUrl = route('dashboard-analytics', request()->except($timeKeys));
  $dailyResetUrl = route('dashboard-analytics', request()->except($dailyKeys));

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
@endphp

@section('content')
  <div class="card mb-5 dashboard-section">
    <div class="card-header">
      <h5 class="mb-0">Bảng tổng nhanh</h5>
    </div>
    <div class="card-body border-top">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="row g-3 align-items-end">
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

  <div class="card dashboard-section">
    <div class="card-header">
      <h5 class="mb-0">Sản lượng theo ngày</h5>
    </div>
    <div class="card-body border-top pb-0">
      <form action="{{ route('dashboard-analytics') }}" method="GET" class="row g-3 align-items-end">
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
        <div class="col-12 col-xl-6">
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
  </div>
@endsection
