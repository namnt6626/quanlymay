@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard')

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
      'time_date_from',
      'time_date_to',
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
      ['label' => 'Tồn kho', 'value' => $quickSummary['ton_kho'] ?? 0, 'icon' => 'bx-package', 'class' => 'primary'],
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
      ['label' => 'Tồn kho', 'value' => $timeProductionSummary['ton_kho'] ?? 0, 'icon' => 'bx-package', 'class' => 'primary'],
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
  <div class="card mb-5">
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
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-filter-alt me-1"></i> Lọc
            </button>
            <a href="{{ $quickResetUrl }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>

      <div class="row g-4 mt-1">
        @foreach ($quickCards as $card)
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100 shadow-none border">
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

  <div class="card mb-5">
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
          <label class="form-label" for="time_date_from">Từ ngày</label>
          <input type="date" class="form-control" id="time_date_from" name="time_date_from"
            value="{{ $timeFilters['date_from'] ?? '' }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="time_date_to">Đến ngày</label>
          <input type="date" class="form-control" id="time_date_to" name="time_date_to"
            value="{{ $timeFilters['date_to'] ?? '' }}">
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
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-filter-alt me-1"></i> Lọc
            </button>
            <a href="{{ $timeResetUrl }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>

      <div class="row g-4 mt-1">
        @foreach ($timeCards as $card)
          <div class="col-12 col-sm-6 col-lg-4 col-xxl">
            <div class="card h-100 shadow-none border">
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

  <div class="card mb-5">
    <div class="card-header">
      <h5 class="mb-0">Chỉ số hôm nay</h5>
    </div>
    <div class="card-body border-top">
      <div class="row g-4">
        @foreach ($todayCards as $card)
          <div class="col-12 col-sm-6 col-lg-4 col-xxl-2">
            <div class="card h-100 shadow-none border">
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

  <div class="card">
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
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-filter-alt me-1"></i> Lọc
            </button>
            <a href="{{ $dailyResetUrl }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>
    </div>
    <div class="table-responsive mt-3">
      <table class="table align-middle">
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
              <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
              <td class="text-end">{{ $formatNumber($row['cat']) }}</td>
              <td class="text-end">{{ $formatNumber($row['giao_may']) }}</td>
              <td class="text-end">{{ $formatNumber($row['qc_dat']) }}</td>
              <td class="text-end">{{ $formatNumber($row['qc_loi']) }}</td>
              <td class="text-end">{{ $formatNumber($row['nhap_kho']) }}</td>
              <td class="text-end">{{ $formatNumber($row['xuat_hang']) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4">Chưa có dữ liệu sản lượng.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
