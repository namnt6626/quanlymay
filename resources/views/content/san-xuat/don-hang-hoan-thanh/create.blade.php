@extends('layouts/contentNavbarLayout')
@section('title', 'Thêm đơn hàng hoàn thành')
@section('content')
<h4 class="mb-4">Thêm đơn hàng hoàn thành</h4>
<form method="POST" action="{{ route('don-hang-hoan-thanh.store') }}">@csrf @include('content.san-xuat.don-hang-hoan-thanh._form')</form>
@endsection
