@extends('layouts/contentNavbarLayout')
@section('title', 'Thêm nhập hàng')
@section('content')
<h4 class="mb-4">Thêm nhập hàng</h4>
<form method="POST" action="{{ route('nhap-hang-online.store') }}">@csrf @include('content.don-hang-online.nhap-hang._form')</form>
@endsection
