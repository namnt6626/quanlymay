@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm người dùng')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Thêm người dùng</h5>
      <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>
    <div class="card-body">
      <form action="{{ route('user.store') }}" method="POST">
        @csrf
        @include('content.tai-khoan.user._form')
      </form>
    </div>
  </div>
@endsection
