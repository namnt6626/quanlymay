@extends('layouts/contentNavbarLayout')

@section('title', 'Đổi mật khẩu')

@section('content')
  @include('content.danh-muc._toast')

  <div class="row g-4">
    <div class="col-12 col-xl-4">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="mb-3">{{ $user->name }}</h5>
          <div class="text-muted mb-2">{{ $user->role?->ten_vai_tro ?: '-' }}</div>
          <div class="mb-2"><strong>Tên đăng nhập:</strong> {{ $user->username }}</div>
          <div class="mb-2"><strong>Email:</strong> {{ $user->email ?: '-' }}</div>
          <div><strong>Trạng thái:</strong> {{ $user->status ? 'Hoạt động' : 'Khóa' }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-8">
      <div class="card">
        <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
          <h5 class="mb-0">Đổi mật khẩu</h5>
          <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
            <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
          </a>
        </div>
        <div class="card-body">
          <form action="{{ route('profile.update-password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
              <div class="col-12">
                <label class="form-label" for="current_password">Mật khẩu hiện tại <span
                    class="text-danger">*</span></label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                  id="current_password" name="current_password" required>
                @error('current_password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="password">Mật khẩu mới <span class="text-danger">*</span></label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                  name="password" minlength="8" required>
                @error('password')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="password_confirmation">Xác nhận mật khẩu mới <span
                    class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                  minlength="8" required>
              </div>

              <div class="col-12">
                <button type="submit" class="btn btn-primary">
                  <i class="icon-base bx bx-lock-alt me-1"></i> Cập nhật mật khẩu
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
