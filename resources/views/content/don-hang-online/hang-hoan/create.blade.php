@extends('layouts/contentNavbarLayout')
@section('title', 'Thêm hàng hoàn')
@section('content')
<form method="POST" action="{{ route('hang-hoan-online.store') }}">
  @csrf
  @include('content.don-hang-online.hang-hoan._form')
</form>
@endsection
