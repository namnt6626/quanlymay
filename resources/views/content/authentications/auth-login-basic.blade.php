@extends('layouts/blankLayout')

@section('title', 'Đăng nhập')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">
        <div class="card px-sm-6 px-0">
          <div class="card-body">
            <div class="app-brand justify-content-center mb-6">
              <a href="{{ url('/') }}" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">@include('_partials.macros')</span>
                <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
              </a>
            </div>

            <h4 class="mb-1">Đăng nhập hệ thống</h4>
            <p class="mb-6">Sử dụng tên đăng nhập và mật khẩu của bạn để tiếp tục.</p>

            <form id="formAuthentication" class="mb-6" action="{{ route('login') }}" method="POST">
              @csrf

              <div class="mb-6">
                <label for="username" class="form-label">Tên đăng nhập</label>
                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                  name="username" value="{{ old('username') }}" placeholder="Nhập tên đăng nhập" autofocus>
                @error('username')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-6 form-password-toggle">
                <label class="form-label" for="password">Mật khẩu</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                    name="password" placeholder="Nhập mật khẩu" aria-describedby="password">
                  <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
                  @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="mb-8">
                <div class="d-flex justify-content-end align-items-center flex-wrap gap-2">
                  <a href="{{ url('auth/forgot-password-basic') }}">
                    <span>Quên mật khẩu?</span>
                  </a>
                </div>
              </div>

              <div class="mb-6">
                <button class="btn btn-primary d-grid w-100" type="submit">Đăng nhập</button>
              </div>
            </form>

            <p class="text-center">
              <span>Chưa có tài khoản?</span>
              <a href="{{ url('auth/register-basic') }}">
                <span>Đăng ký</span>
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
