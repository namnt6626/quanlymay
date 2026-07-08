@extends('layouts/contentNavbarLayout')
@section('title', 'Xem trước dữ liệu Excel')
@section('page-style')
<style>
  .excel-preview-scroll {
    overflow-x: auto;
    max-width: 100%;
    scrollbar-gutter: stable;
  }
  .excel-preview-table {
    min-width: 1750px;
    table-layout: fixed;
  }
  .excel-preview-table th,
  .excel-preview-table td {
    vertical-align: top;
  }
  .excel-preview-table .col-row { width: 70px; }
  .excel-preview-table .col-date { width: 150px; }
  .excel-preview-table .col-product { width: 390px; }
  .excel-preview-table .col-variation { width: 260px; }
  .excel-preview-table .col-color { width: 220px; }
  .excel-preview-table .col-size { width: 110px; }
  .excel-preview-table .col-quantity { width: 110px; }
  .excel-preview-table .col-money { width: 180px; }
  .excel-preview-original {
    white-space: normal;
    overflow-wrap: anywhere;
    line-height: 1.4;
  }
</style>
@endsection
@section('content')
<h4 class="mb-3">Xem trước dữ liệu Excel</h4>
<div class="alert alert-info">Tìm thấy <strong>{{ count($rows) }}</strong> dòng hợp lệ. Có thể sửa trực tiếp Màu/Size trước khi nhập.</div>
@if($ignored)<div class="alert alert-warning">Đã bỏ qua dòng không phải dữ liệu hoặc không hợp lệ: {{ implode(', ', $ignored) }}.</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('don-hang-hoan-thanh.commit') }}">@csrf
<div class="card"><div class="excel-preview-scroll"><table class="table table-sm align-middle excel-preview-table">
  <thead><tr><th class="col-row">Dòng</th><th class="col-date">Ngày</th><th class="col-product">Sản phẩm</th><th class="col-variation">Phân loại gốc</th><th class="col-color">Màu</th><th class="col-size">Size</th><th class="col-quantity">SL</th><th class="col-money">Thành tiền</th></tr></thead>
  <tbody>@foreach($rows as $index => $row)<tr>
    <td>{{ $row['dong_excel'] }}<input type="hidden" name="rows[{{ $index }}][dong_excel]" value="{{ $row['dong_excel'] }}"></td>
    <td><input type="date" class="form-control form-control-sm" name="rows[{{ $index }}][ngay_hoan_thanh]" value="{{ $row['ngay_hoan_thanh'] }}" required><input type="hidden" name="rows[{{ $index }}][thoi_gian_tao_goc]" value="{{ $row['thoi_gian_tao_goc'] }}"></td>
    <td><input class="form-control form-control-sm" name="rows[{{ $index }}][ten_san_pham]" value="{{ $row['ten_san_pham'] }}" required><input type="hidden" name="rows[{{ $index }}][kenh_ban]" value="{{ $row['kenh_ban'] }}"></td>
    <td><div class="small excel-preview-original">{{ $row['phan_loai_goc'] ?: '-' }}</div><input type="hidden" name="rows[{{ $index }}][phan_loai_goc]" value="{{ $row['phan_loai_goc'] }}"></td>
    <td><input class="form-control form-control-sm" name="rows[{{ $index }}][mau]" value="{{ $row['mau'] }}"></td>
    <td><input class="form-control form-control-sm" name="rows[{{ $index }}][size]" value="{{ $row['size'] }}"></td>
    <td><input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm" name="rows[{{ $index }}][so_luong]" value="{{ $row['so_luong'] }}" required></td>
    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="rows[{{ $index }}][thanh_tien]" value="{{ $row['thanh_tien'] }}" required><div class="small text-muted">{{ number_format($row['thanh_tien'], 0, ',', '.') }} ₫</div></td>
  </tr>@endforeach</tbody>
</table></div></div>
<div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route('don-hang-hoan-thanh.import') }}">Chọn file khác</a><button class="btn btn-success"><i class="icon-base bx bx-check me-1"></i>Xác nhận nhập {{ count($rows) }} dòng</button></div>
</form>
@endsection
