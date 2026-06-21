@extends('layouts/contentNavbarLayout')

@section('title', 'Sửa mã hàng')

@section('content')
@include('content.danh-muc._toast')

<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
        <h5 class="mb-0">Sửa mã hàng</h5>
        <a href="{{ route('mat-hang.index') }}" class="btn btn-outline-secondary">
            <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('mat-hang.update', $matHang) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label" for="ma_hang">Mã hàng <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ma_hang') is-invalid @enderror" id="ma_hang" name="ma_hang" value="{{ old('ma_hang', $matHang->ma_hang) }}" maxlength="50" required>
                    @error('ma_hang')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="ten_hang">Tên hàng <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('ten_hang') is-invalid @enderror" id="ten_hang" name="ten_hang" value="{{ old('ten_hang', $matHang->ten_hang) }}" required>
                    @error('ten_hang')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="mo_ta">Mô tả</label>
                    <textarea class="form-control @error('mo_ta') is-invalid @enderror" id="mo_ta" name="mo_ta" rows="4">{{ old('mo_ta', $matHang->mo_ta) }}</textarea>
                    @error('mo_ta')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                    <select class="form-select @error('trang_thai') is-invalid @enderror" id="trang_thai" name="trang_thai" required>
                        <option value="1" @selected((string) old('trang_thai', (int) $matHang->trang_thai) === '1')>Hoạt động</option>
                        <option value="0" @selected((string) old('trang_thai', (int) $matHang->trang_thai) === '0')>Ngừng dùng</option>
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
                        <a href="{{ route('mat-hang.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
