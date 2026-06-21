@extends('layouts/contentNavbarLayout')

@section('title', 'Cập nhật đơn hàng')

@section('page-script')
  @include('content.don-hangs._script')
@endsection

@section('content')
  @include('content.danh-muc._toast')
  @include('content.don-hangs._form', [
      'action' => route('don-hangs.update', $donHang),
      'method' => 'PUT',
      'submitLabel' => 'Cập nhật',
      'backRoute' => route('don-hangs.index'),
      'donHang' => $donHang,
      'detailRows' => $detailRows,
      'matHangs' => $matHangs,
      'maus' => $maus,
      'sizes' => $sizes,
  ])
@endsection
