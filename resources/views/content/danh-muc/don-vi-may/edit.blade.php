@extends('layouts/contentNavbarLayout')

@section('title', 'Sửa đơn vị may')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Sửa đơn vị may</h5>
      <a href="{{ route('don-vi-may.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>
    <div class="card-body">
      <form action="{{ route('don-vi-may.update', $donViMay) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <div class="col-md-4">
            <label class="form-label" for="ma_don_vi">Mã đơn vị <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('ma_don_vi') is-invalid @enderror" id="ma_don_vi"
              name="ma_don_vi" value="{{ old('ma_don_vi', $donViMay->ma_don_vi) }}" maxlength="50" required>
            @error('ma_don_vi')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-8">
            <label class="form-label" for="ten_don_vi">Tên đơn vị <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('ten_don_vi') is-invalid @enderror" id="ten_don_vi"
              name="ten_don_vi" value="{{ old('ten_don_vi', $donViMay->ten_don_vi) }}" required>
            @error('ten_don_vi')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
            <select class="form-select @error('trang_thai') is-invalid @enderror" id="trang_thai" name="trang_thai"
              required>
              <option value="1" @selected((string) old('trang_thai', (int) $donViMay->trang_thai) === '1')>Hoạt động</option>
              <option value="0" @selected((string) old('trang_thai', (int) $donViMay->trang_thai) === '0')>Ngừng dùng</option>
            </select>
            @error('trang_thai')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Lưu
              </button>
              <a href="{{ route('don-vi-may.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
