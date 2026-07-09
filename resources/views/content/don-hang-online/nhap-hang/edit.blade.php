@extends('layouts/contentNavbarLayout')
@section('title', 'Sửa nhập hàng')
@section('content')
<h4 class="mb-4">Sửa nhập hàng</h4>
<form method="POST" action="{{ route('nhap-hang-online.update', $import) }}">@csrf @method('PUT') @include('content.don-hang-online.nhap-hang._form')</form>
@endsection
