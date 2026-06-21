@extends('layouts/contentNavbarLayout')

@section('title', 'Hồ sơ cá nhân')

@section('content')
  @include('content.danh-muc._toast')

  <div class="row g-4">
    <div class="col-12 col-xl-4">
      <div class="card h-100">
        <div class="card-body text-center">
          <div
            class="avatar avatar-xl bg-primary text-white d-flex align-items-center justify-content-center fw-bold mx-auto mb-4"
            style="width: 5rem; height: 5rem;">
            <span>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
          </div>
          <h5 class="mb-1">{{ $user->name }}</h5>
          <div class="text-muted mb-3">{{ $user->role?->ten_vai_tro ?: '-' }}</div>
          @if ($user->status)
            <span class="badge bg-label-success">Hoạt động</span>
          @else
            <span class="badge bg-label-secondary">Khóa</span>
          @endif

          <hr class="my-4">

          <div class="text-start small">
            <div class="mb-2"><strong>Tên đăng nhập:</strong> {{ $user->username }}</div>
            <div class="mb-2"><strong>Ngày tạo:</strong> {{ $user->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
            <div><strong>Lần đăng nhập cuối:</strong> {{ $user->last_login_at?->format('d/m/Y H:i') ?: '-' }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-8">
      <div class="card mb-4">
        <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
          <h5 class="mb-0">Thông tin cá nhân</h5>
          <a href="{{ route('profile.change-password') }}" class="btn btn-outline-primary">
            <i class="icon-base bx bx-lock-alt me-1"></i> Đổi mật khẩu
          </a>
        </div>
        <div class="card-body">
          <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label" for="name">Họ tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                  name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="username">Tên đăng nhập</label>
                <input type="text" class="form-control" id="username" value="{{ $user->username }}" disabled>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                  name="email" value="{{ old('email', $user->email) }}">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label" for="phone">Số điện thoại</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone"
                  name="phone" value="{{ old('phone', $user->phone) }}">
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label class="form-label" for="role">Vai trò</label>
                <input type="text" class="form-control" id="role"
                  value="{{ $user->role?->ma_vai_tro ? $user->role->ma_vai_tro . ' - ' . $user->role->ten_vai_tro : '-' }}"
                  disabled>
              </div>

              <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                  <button type="submit" class="btn btn-primary">
                    <i class="icon-base bx bx-save me-1"></i> Lưu thay đổi
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Thông tin tài khoản</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><strong>Họ tên:</strong> {{ $user->name }}</div>
            <div class="col-md-6"><strong>Tên đăng nhập:</strong> {{ $user->username }}</div>
            <div class="col-md-6"><strong>Email:</strong> {{ $user->email ?: '-' }}</div>
            <div class="col-md-6"><strong>Số điện thoại:</strong> {{ $user->phone ?: '-' }}</div>
            <div class="col-md-6"><strong>Vai trò:</strong> {{ $user->role?->ten_vai_tro ?: '-' }}</div>
            <div class="col-md-6"><strong>Trạng thái:</strong> {{ $user->status ? 'Hoạt động' : 'Khóa' }}</div>
            <div class="col-md-6"><strong>Ngày tạo tài khoản:</strong>
              {{ $user->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
            <div class="col-md-6"><strong>Lần đăng nhập cuối:</strong>
              {{ $user->last_login_at?->format('d/m/Y H:i') ?: '-' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
