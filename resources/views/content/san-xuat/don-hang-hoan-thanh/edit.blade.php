@extends('layouts/contentNavbarLayout')
@section('title', 'Sửa đơn hàng hoàn thành')
@section('content')
<h4 class="mb-4">Sửa đơn hàng hoàn thành</h4>
<form method="POST" action="{{ route('don-hang-hoan-thanh.update', $order) }}">@csrf @method('PUT') @include('content.san-xuat.don-hang-hoan-thanh._form')</form>
@endsection
