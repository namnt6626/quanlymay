@extends('layouts/contentNavbarLayout')

@section('title', 'Lần cắt')

@section('content')
  @include('content.danh-muc._toast')

  @php
    $formatCatNumber =
        $formatCatNumber ??
        function ($value) {
            if (function_exists('formatPhanBoNumber')) {
                return formatPhanBoNumber($value);
            }

            if ($value === null || $value === '') {
                return '-';
            }

            $number = (float) $value;

            if (floor($number) == $number) {
                return number_format($number, 0, ',', '.');
            }

            return rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');
        };
  @endphp

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách lần cắt</h5>
      @if (hasPermission('CAT_CREATE'))
        <a href="{{ route('cat.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('cat.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn, mã KH, mặt hàng, màu hoặc size">
        </div>
        <div class="col-12 col-xl">
          <label class="form-label" for="ngay_cat">Ngày cắt</label>
          <input type="date" class="form-control" id="ngay_cat" name="ngay_cat" value="{{ $ngayCat }}">
        </div>
        @include('content.shared._per-page-select')

        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('cat.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Ngày cắt</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th>Bàn cắt</th>
            <th>Đơn vị cắt</th>
            <th>SL đặt</th>
            <th>Số lượng</th>
            <th>Định mức</th>
            <th>Vải tiêu hao</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($cats as $cat)
            <tr>
              <td>{{ $cats->firstItem() + $loop->index }}</td>
              <td>{{ $cat->ngay_cat ? \Illuminate\Support\Carbon::parse($cat->ngay_cat)->format('d/m/Y') : '-' }}</td>
              <td>
                <strong>{{ $cat->matHang?->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $cat->matHang?->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $cat->mau?->ten_mau ?? '-' }}</td>
              <td>{{ $cat->size?->ten_size ?? '-' }}</td>
              <td>{{ $cat->banCat?->ten_ban ?? '-' }}</td>
              <td>{{ $cat->donViCat?->ten_don_vi ?? '-' }}</td>
              <td>{{ $formatCatNumber($cat->donHangChiTiet?->so_luong_dat) }}</td>
              <td>{{ $formatCatNumber($cat->so_luong_cat) }}</td>
              <td>{{ $formatCatNumber($cat->dinh_muc) }} m</td>
              <td>{{ $formatCatNumber($cat->vai_tieu_hao) }} m</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('CAT_EDIT'))
                    <a href="{{ route('cat.edit', $cat) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('CAT_DELETE'))
                    <form action="{{ route('cat.destroy', $cat) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa lần cắt này?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Xóa">
                        <i class="icon-base bx bx-trash"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="14" class="text-center py-4">Chưa có dữ liệu lần cắt.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($cats->hasPages())
      <div class="card-footer">
        {{ $cats->links() }}
      </div>
    @endif
  </div>
@endsection
