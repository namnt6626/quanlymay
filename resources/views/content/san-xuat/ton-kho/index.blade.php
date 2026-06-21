@extends('layouts/contentNavbarLayout')

@section('title', 'Tồn kho')

@section('page-style')
  <style>
    .ton-kho-table-wrap {
      max-height: calc(100vh - 260px);
      overflow: auto;
    }

    .ton-kho-table {
      border-collapse: separate;
      border-spacing: 0;
      min-width: 1500px;
    }

    .ton-kho-table thead th {
      background-color: var(--bs-card-bg);
      box-shadow: inset 0 -1px 0 var(--bs-border-color);
      position: sticky;
      top: 0;
      white-space: nowrap;
      z-index: 5;
    }
  </style>
@endsection

@php
  $formatNumber =
      $formatNumber ??
      function ($value) {
          if ($value === null || $value === '') {
              return '-';
          }

          $number = (float) $value;

          if (floor($number) == $number) {
              return number_format($number, 0, ',', '.');
          }

          return rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');
      };
@endphp

@section('content')
  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Báo cáo tồn kho</h5>
    </div>

    <div class="card-body">
      <form action="{{ route('ton-kho.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn, mã KH, mã hàng, màu hoặc size">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ma_don">Mã đơn</label>
          <input type="text" class="form-control" id="ma_don" name="ma_don" value="{{ $maDon }}"
            placeholder="Mã đơn">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ma_kh">Mã KH</label>
          <input type="text" class="form-control" id="ma_kh" name="ma_kh" value="{{ $maKh }}"
            placeholder="Mã KH">
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="mat_hang_id">Mặt hàng</label>
          <select class="form-select" id="mat_hang_id" name="mat_hang_id">
            <option value="">Tất cả</option>
            @foreach ($matHangs as $matHang)
              <option value="{{ $matHang->id }}" @selected((int) $matHangId === (int) $matHang->id)>
                {{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="mau_id">Màu</label>
          <select class="form-select" id="mau_id" name="mau_id">
            <option value="">Tất cả</option>
            @foreach ($maus as $mau)
              <option value="{{ $mau->id }}" @selected((int) $mauId === (int) $mau->id)>
                {{ $mau->ten_mau }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="size_id">Size</label>
          <select class="form-select" id="size_id" name="size_id">
            <option value="">Tất cả</option>
            @foreach ($sizes as $size)
              <option value="{{ $size->id }}" @selected((int) $sizeId === (int) $size->id)>
                {{ $size->ten_size }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="trang_thai">Trạng thái</label>
          <select class="form-select" id="trang_thai" name="trang_thai">
            <option value="">Tất cả</option>
            <option value="con-hang" @selected($trangThai === 'con-hang')>Còn hàng</option>
            <option value="het-hang" @selected($trangThai === 'het-hang')>Hết hàng</option>
            <option value="am-kho" @selected($trangThai === 'am-kho')>Âm kho</option>
          </select>
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('ton-kho.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive ton-kho-table-wrap">
      <table class="table align-middle ton-kho-table">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">SL đặt</th>
            <th class="text-end">Đã cắt</th>
            <th class="text-end">QC đạt</th>
            <th class="text-end">QC lỗi</th>
            <th class="text-end">QC hỏng</th>
            <th class="text-end">Tồn đạt</th>
            <th class="text-end">Tồn lỗi</th>
            <th class="text-end">Tồn hỏng</th>
            <th class="text-end">Đã xuất</th>
            <th class="text-end">Tồn có thể xuất</th>
            <th class="text-end">Tồn tổng</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($tonKhos as $tonKho)
            <tr>
              <td>{{ $tonKhos->firstItem() + $loop->index }}</td>
              <td>{{ $tonKho->ma_don ?? '-' }}</td>
              <td>{{ $tonKho->ma_kh ?? '-' }}</td>
              <td>
                <strong>{{ $tonKho->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $tonKho->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $tonKho->ten_mau ?? '-' }}</td>
              <td>{{ $tonKho->ten_size ?? '-' }}</td>
              <td class="text-end">{{ $tonKho->so_luong_dat !== null ? $formatNumber($tonKho->so_luong_dat) : '-' }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->da_cat) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->qc_dat) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->qc_loi) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->qc_hong) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->ton_dat) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->ton_loi) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->ton_hong) }}</td>
              <td class="text-end">{{ $formatNumber($tonKho->da_xuat) }}</td>
              <td class="text-end fw-semibold">{{ $formatNumber($tonKho->ton_co_the_xuat) }}</td>
              <td class="text-end fw-semibold">{{ $formatNumber($tonKho->tong_ton_vat_ly) }}</td>
              <td>
                @if ((float) $tonKho->ton_co_the_xuat > 0)
                  <span class="badge bg-label-success">Còn hàng</span>
                @elseif ((float) $tonKho->ton_co_the_xuat < 0)
                  <span class="badge bg-label-warning">Âm kho</span>
                @else
                  <span class="badge bg-label-danger">Hết hàng</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="18" class="text-center py-4">Chưa có dữ liệu tồn kho.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($tonKhos->hasPages())
      <div class="card-footer">
        {{ $tonKhos->links() }}
      </div>
    @endif
  </div>
@endsection
