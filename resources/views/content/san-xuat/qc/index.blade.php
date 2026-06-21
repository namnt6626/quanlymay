@extends('layouts/contentNavbarLayout')

@section('title', 'QC')

@section('content')
  @include('content.danh-muc._toast')

  @php
    $formatPhanBoNumber =
        $formatPhanBoNumber ??
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
      <h5 class="mb-0">Danh sách QC</h5>
      @if (hasPermission('QC_CREATE'))
        <a href="{{ route('qc.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('qc.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập mã đơn, mã KH, mã hàng, tên hàng, màu, size hoặc đơn vị may">
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('qc.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Ngày QC</th>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th class="text-end">SL đặt</th>
            <th class="text-end">SL cắt</th>
            <th>Đơn vị may</th>
            <th class="text-end">SL QC</th>
            <th class="text-end">SL đạt</th>
            <th class="text-end">SL lỗi</th>
            <th class="text-end">SL hỏng</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($qcs as $qc)
            @php
              $matHang = $qc->phanBoMay?->cat?->matHang ?? $qc->matHang;
              $mau = $qc->phanBoMay?->cat?->mau ?? $qc->mau;
              $size = $qc->phanBoMay?->cat?->size ?? $qc->size;
            @endphp
            <tr>
              <td>{{ $qcs->firstItem() + $loop->index }}</td>
              <td>{{ $qc->ngay_qc ? \Illuminate\Support\Carbon::parse($qc->ngay_qc)->format('d/m/Y') : '-' }}</td>
              <td>{{ $qc->donHangChiTiet?->donHang?->ma_don ?? '-' }}</td>
              <td>{{ $qc->donHangChiTiet?->donHang?->ma_kh ?? '-' }}</td>
              <td>
                <strong>{{ $matHang?->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $matHang?->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $mau?->ten_mau ?? '-' }}</td>
              <td>{{ $size?->ten_size ?? '-' }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($qc->donHangChiTiet?->so_luong_dat) }}</td>
              <td class="text-end">
                {{ $formatPhanBoNumber($qc->source_total_cut ?? ($qc->phanBoMay?->source_total_cut ?? $qc->phanBoMay?->cat?->so_luong_cat)) }}
              </td>
              <td>{{ $qc->phanBoMay?->donViMay?->ten_don_vi ?? '-' }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($qc->so_luong_qc) }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($qc->so_luong_dat) }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($qc->so_luong_loi) }}</td>
              <td class="text-end">{{ $formatPhanBoNumber($qc->so_luong_hong) }}</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('QC_EDIT'))
                    <a href="{{ route('qc.edit', $qc) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('QC_DELETE'))
                    <form action="{{ route('qc.destroy', $qc) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa QC này?');">
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
              <td colspan="15" class="text-center py-4">Chưa có dữ liệu QC.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($qcs->hasPages())
      <div class="card-footer">
        {{ $qcs->links() }}
      </div>
    @endif
  </div>
@endsection
