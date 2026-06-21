@extends('layouts/contentNavbarLayout')

@section('title', 'Tổng hợp đơn hàng')

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
@endphp

@section('content')
  <div class="card mb-4">
    <div class="card-body">
      <form action="{{ route('bao-cao.tong-hop-don-hang') }}" method="GET" class="row g-3 align-items-end">
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
          <input type="text" class="form-control" id="ma_kh" name="ma_kh" value="{{ $maKh }}" placeholder="Mã KH">
        </div>
        <div class="col-12 col-md-4 col-xl-2">
          <label class="form-label" for="mat_hang_id">Mã hàng</label>
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
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ngay_nhan_tu">Ngày nhận từ</label>
          <input type="date" class="form-control" id="ngay_nhan_tu" name="ngay_nhan_tu" value="{{ $ngayNhanTu }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="ngay_nhan_den">Ngày nhận đến</label>
          <input type="date" class="form-control" id="ngay_nhan_den" name="ngay_nhan_den" value="{{ $ngayNhanDen }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="han_giao_tu">Hạn giao từ</label>
          <input type="date" class="form-control" id="han_giao_tu" name="han_giao_tu" value="{{ $hanGiaoTu }}">
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label" for="han_giao_den">Hạn giao đến</label>
          <input type="date" class="form-control" id="han_giao_den" name="han_giao_den" value="{{ $hanGiaoDen }}">
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('bao-cao.tong-hop-don-hang') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle">
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
          @forelse ($rows as $row)
            <tr>
              <td>{{ $row->ma_don }}</td>
              <td>{{ $row->ma_kh }}</td>
              <td>
                <strong>{{ $row->ma_hang }}</strong>
                <div class="text-muted small">{{ $row->ten_hang }}</div>
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
            <tr>
              <td colspan="15" class="text-center py-4">Chưa có dữ liệu báo cáo.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($rows->hasPages())
      <div class="card-footer">
        {{ $rows->links() }}
      </div>
    @endif
  </div>
@endsection
