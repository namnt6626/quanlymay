@extends('layouts/contentNavbarLayout')
@section('title', 'Import đơn hàng hoàn thành')
@section('content')
<h4 class="mb-4">Import đơn hàng hoàn thành</h4>
<div class="card"><div class="card-body">
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <form method="POST" enctype="multipart/form-data" action="{{ route('don-hang-hoan-thanh.preview') }}">@csrf
    <label class="form-label">File Excel (.xlsx) <span class="text-danger">*</span></label>
    <input type="file" name="file_excel" class="form-control" accept=".xlsx" required>
    <div class="form-text">Hệ thống tự tìm dòng tiêu đề, tự bỏ dòng giải thích và tách Màu/Size. Tối đa 10 MB.</div>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route('don-hang-hoan-thanh.index') }}">Hủy</a><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Đọc và xem trước</button></div>
  </form>
</div></div>
@endsection
