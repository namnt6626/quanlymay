@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm đơn hàng')

@section('page-script')
  @include('content.don-hangs._script')
@endsection

@section('content')
  @include('content.danh-muc._toast')
  @include('content.don-hangs._form', [
      'action' => route('don-hangs.store'),
      'method' => 'POST',
      'submitLabel' => 'Lưu',
      'backRoute' => route('don-hangs.index'),
      'donHang' => null,
      'detailRows' => $detailRows,
      'matHangs' => $matHangs,
      'maus' => $maus,
      'sizes' => $sizes,
  ])
@endsection
