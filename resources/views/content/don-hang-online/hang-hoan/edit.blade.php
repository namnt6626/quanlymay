@extends('layouts/contentNavbarLayout')
@section('title', 'Sửa hàng hoàn')
@section('content')
<form method="POST" action="{{ route('hang-hoan-online.update', $returnBatch) }}">
  @csrf
  @method('PUT')
  @include('content.don-hang-online.hang-hoan._form')
</form>
@endsection
