@extends('layouts/blankLayout')

@section('title', 'Không có quyền truy cập')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@php
  $user = auth()->user();
  $firstAccessibleRoute = $user ? app(\App\Support\AccessRedirect::class)->firstAccessibleRoute($user) : null;
  $message = $firstAccessibleRoute
      ? 'Tài khoản này chưa được cấp quyền truy cập màn này.'
      : 'Tài khoản này chưa được cấp quyền truy cập hệ thống. Vui lòng liên hệ quản trị viên.';
@endphp

@section('content')
  <div class="container-xxl container-p-y">
    <div class="misc-wrapper text-center">
      <h1 class="mb-2 mx-2">403</h1>
      <h4 class="mb-2">Không có quyền truy cập</h4>
      <p class="mb-6 mx-2">{{ $message }}</p>

      @if ($firstAccessibleRoute)
        <a href="{{ route($firstAccessibleRoute) }}" class="btn btn-primary">
          Về màn được phép truy cập
        </a>
      @elseif ($user)
        <form action="{{ route('logout') }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-primary">
            Đăng xuất
          </button>
        </form>
      @else
        <a href="{{ route('login') }}" class="btn btn-primary">
          Về màn đăng nhập
        </a>
      @endif
    </div>
  </div>
@endsection
