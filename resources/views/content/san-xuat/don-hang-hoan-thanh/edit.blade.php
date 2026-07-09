@extends('layouts/contentNavbarLayout')
@section('title', 'Sửa xuất hàng')
@section('content')
<h4 class="mb-4">Sửa xuất hàng</h4>
<form method="POST" action="{{ route('don-hang-hoan-thanh.update', $order) }}">@csrf @method('PUT') @include('content.san-xuat.don-hang-hoan-thanh._form')</form>
@endsection
