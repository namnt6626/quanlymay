@extends('layouts/contentNavbarLayout')
@section('title', 'Tồn kho')
@section('page-style')
@include('content.san-xuat._filter-style')
<style>
  .online-stock-scroll {
    overflow: visible;
    scrollbar-gutter: stable;
  }
  .online-stock-table {
    margin-bottom: 0;
  }
  .online-stock-table thead th {
    background: var(--bs-gray-100);
    color: var(--bs-heading-color);
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 3;
  }
  .online-stock-table thead tr.stock-total-row th {
    background: var(--bs-body-bg);
    border-bottom-width: 2px;
    box-shadow: 0 2px 0 rgba(67, 89, 113, .08);
    position: sticky;
    top: 43px;
    z-index: 2;
  }
  .online-stock-table td {
    vertical-align: top;
  }
</style>
@endsection
@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Tồn kho online</h5></div>
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Sản phẩm</label>
        <input class="form-control" name="ten_san_pham" value="{{ $filters['ten_san_pham'] }}" list="ton-kho-products" placeholder="Gõ hoặc chọn sản phẩm">
        <datalist id="ton-kho-products">@foreach($filterOptions['products'] as $product)<option value="{{ $product }}"></option>@endforeach</datalist>
      </div>
      <div class="col-md-2"><label class="form-label">Màu</label><select class="form-select" name="mau"><option value="">Tất cả</option>@foreach($filterOptions['colors'] as $color)<option value="{{ $color }}" @selected($filters['mau'] === $color)>{{ $color }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Size</label><select class="form-select" name="size"><option value="">Tất cả</option>@foreach($filterOptions['sizes'] as $size)<option value="{{ $size }}" @selected($filters['size'] === $size)>{{ $size }}</option>@endforeach</select></div>
      <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('ton-kho-online.index') }}" class="btn btn-outline-secondary">Mới</a></div>
    </form>
  </div>
  <div class="online-stock-scroll">
    <table class="table online-stock-table">
      <thead>
        <tr><th>Tên sản phẩm</th><th>Màu</th><th>Size</th><th class="text-end">SL nhập</th><th class="text-end">SL xuất</th><th class="text-end">Tồn</th><th class="text-end">Tiền nhập</th><th class="text-end">Tiền xuất</th><th class="text-end">Tiền xuất - nhập</th></tr>
        <tr class="stock-total-row"><th colspan="3" class="text-end">Tổng</th><th class="text-end">{{ number_format($totals['so_luong_nhap'], 0, ',', '.') }}</th><th class="text-end">{{ number_format($totals['so_luong_xuat'], 0, ',', '.') }}</th><th class="text-end">{{ number_format($totals['so_luong_ton'], 0, ',', '.') }}</th><th class="text-end">{{ number_format($totals['tien_nhap'], 0, ',', '.') }} ₫</th><th class="text-end">{{ number_format($totals['tien_xuat'], 0, ',', '.') }} ₫</th><th class="text-end">{{ number_format($totals['chenh_lech_tien'], 0, ',', '.') }} ₫</th></tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        <tr>
          <td class="fw-semibold">{{ $row['ten_san_pham'] }}</td>
          <td>{{ $row['mau'] ?: '-' }}</td>
          <td>{{ $row['size'] ?: '-' }}</td>
          <td class="text-end">{{ number_format($row['so_luong_nhap'], 0, ',', '.') }}</td>
          <td class="text-end">{{ number_format($row['so_luong_xuat'], 0, ',', '.') }}</td>
          <td class="text-end fw-semibold {{ $row['so_luong_ton'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['so_luong_ton'], 0, ',', '.') }}</td>
          <td class="text-end">{{ number_format($row['tien_nhap'], 0, ',', '.') }} ₫</td>
          <td class="text-end">{{ number_format($row['tien_xuat'], 0, ',', '.') }} ₫</td>
          <td class="text-end fw-semibold {{ $row['chenh_lech_tien'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['chenh_lech_tien'], 0, ',', '.') }} ₫</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center py-4">Chưa có dữ liệu.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
