@extends('layouts/contentNavbarLayout')
@section('title', 'Import xuất hàng')
@section('content')
<h4 class="mb-4">Import xuất hàng</h4>
<div class="card"><div class="card-body">
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <form method="POST" enctype="multipart/form-data" action="{{ route('don-hang-hoan-thanh.preview') }}">@csrf
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Kênh bán <span class="text-danger">*</span></label>
        <select name="kenh_ban" class="form-select" required>
          @foreach($kenhBans as $kenhBan)
            <option value="{{ $kenhBan }}" @selected(old('kenh_ban', $kenhBans->first()) === $kenhBan)>{{ $kenhBan }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">File Excel (.xlsx) <span class="text-danger">*</span></label>
        <input type="file" name="file_excel" class="form-control" accept=".xlsx" required>
        <div class="form-text">Hỗ trợ file xuất hàng Shopee/Tiktok dạng cột chuẩn hoặc file đóng gói có cột product_info. Tự tách Màu/Size. Tối đa 10 MB.</div>
      </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route('don-hang-hoan-thanh.index') }}">Hủy</a><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Đọc và xem trước</button></div>
  </form>
</div></div>
@endsection
