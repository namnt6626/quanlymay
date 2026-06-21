@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm màu sắc')

@section('content')
@include('content.danh-muc._toast')

<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
        <h5 class="mb-0">Thêm màu sắc</h5>
        <a href="{{ route('mau.index') }}" class="btn btn-outline-secondary">
            <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('mau.store') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label" for="ma_mau">Mã màu <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ma_mau') is-invalid @enderror" id="ma_mau" name="ma_mau" value="{{ old('ma_mau') }}" maxlength="50" required>
                    @error('ma_mau')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="ten_mau">Tên màu <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ten_mau') is-invalid @enderror" id="ten_mau" name="ten_mau" value="{{ old('ten_mau') }}" required>
                    @error('ten_mau')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                    <select class="form-select @error('trang_thai') is-invalid @enderror" id="trang_thai" name="trang_thai" required>
                        <option value="1" @selected(old('trang_thai', '1') === '1')>Hoạt động</option>
                        <option value="0" @selected(old('trang_thai') === '0')>Ngừng dùng</option>
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
                        <a href="{{ route('mau.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
