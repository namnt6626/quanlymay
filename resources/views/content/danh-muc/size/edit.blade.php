@extends('layouts/contentNavbarLayout')

@section('title', 'Sửa size')

@section('content')
@include('content.danh-muc._toast')

<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
        <h5 class="mb-0">Sửa size</h5>
        <a href="{{ route('size.index') }}" class="btn btn-outline-secondary">
            <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('size.update', $size) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label" for="ma_size">Mã size <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ma_size') is-invalid @enderror" id="ma_size" name="ma_size" value="{{ old('ma_size', $size->ma_size) }}" maxlength="50" required>
                    @error('ma_size')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="ten_size">Tên size <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ten_size') is-invalid @enderror" id="ten_size" name="ten_size" value="{{ old('ten_size', $size->ten_size) }}" required>
                    @error('ten_size')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                    <select class="form-select @error('trang_thai') is-invalid @enderror" id="trang_thai" name="trang_thai" required>
                        <option value="1" @selected((string) old('trang_thai', (int) $size->trang_thai) === '1')>Hoạt động</option>
                        <option value="0" @selected((string) old('trang_thai', (int) $size->trang_thai) === '0')>Ngừng dùng</option>
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
                        <a href="{{ route('size.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
