@extends('layouts/contentNavbarLayout')
@section('title', 'Import hàng hoàn')
@section('content')
@if ($errors->any())
  <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Import file hàng hoàn TikTok</h5>
    <a href="{{ route('hang-hoan-online.index') }}" class="btn btn-outline-secondary">Quay lại</a>
  </div>
  <form method="POST" action="{{ route('hang-hoan-online.preview') }}" enctype="multipart/form-data">
    @csrf
    <div class="card-body">
      <label class="form-label">File Excel <span class="text-danger">*</span></label>
      <input type="file" class="form-control" name="file_excel" accept=".xlsx" required>
      <div class="form-text">Chỉ các dòng Completed + Return and refund + số lượng hoàn > 0 mới được cộng tồn.</div>
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-primary"><i class="icon-base bx bx-upload me-1"></i>Kiểm tra dữ liệu</button>
    </div>
  </form>
</div>
@endsection
