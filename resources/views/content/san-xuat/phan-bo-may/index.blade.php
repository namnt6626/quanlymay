@extends('layouts/contentNavbarLayout')

@section('title', 'Phân bổ may')

@section('page-style')
  @include('content.san-xuat._filter-style')
@endsection

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
      <h5 class="mb-0">Danh sách phân bổ may</h5>
      @if (hasPermission('PHAN_BO_MAY_CREATE'))
        <a href="{{ route('phan-bo-may.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('phan-bo-may.index') }}" method="GET" class="row production-filter-form production-filter-grid align-items-end">
        <div class="col-12 col-lg-4 filter-span-4">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Mã đơn, mã KH, mã hàng, màu, size, đơn vị may">
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="tu_ngay">Từ ngày</label>
          <input type="date" class="form-control" id="tu_ngay" name="tu_ngay" value="{{ $filters['tu_ngay'] }}">
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="den_ngay">Đến ngày</label>
          <input type="date" class="form-control" id="den_ngay" name="den_ngay" value="{{ $filters['den_ngay'] }}">
        </div>
        <div class="col-12 col-lg-4 filter-span-4">
          <label class="form-label" for="mat_hang_id">Mã hàng</label>
          <select class="form-select" id="mat_hang_id" name="mat_hang_id">
            <option value="">Tất cả</option>
            @foreach ($matHangs as $matHang)
              <option value="{{ $matHang->id }}" @selected((string) $filters['mat_hang_id'] === (string) $matHang->id)>
                {{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="mau_id">Màu</label>
          <select class="form-select" id="mau_id" name="mau_id">
            <option value="">Tất cả</option>
            @foreach ($maus as $mau)
              <option value="{{ $mau->id }}" @selected((string) $filters['mau_id'] === (string) $mau->id)>{{ $mau->ten_mau }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-lg-2 filter-span-2">
          <label class="form-label" for="size_id">Size</label>
          <select class="form-select" id="size_id" name="size_id">
            <option value="">Tất cả</option>
            @foreach ($sizes as $size)
              <option value="{{ $size->id }}" @selected((string) $filters['size_id'] === (string) $size->id)>{{ $size->ten_size }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-lg-3 filter-span-3">
          <label class="form-label" for="don_vi_may_id">Đơn vị may</label>
          <select class="form-select" id="don_vi_may_id" name="don_vi_may_id">
            <option value="">Tất cả</option>
            @foreach ($donViMays as $donViMay)
              <option value="{{ $donViMay->id }}" @selected((string) $filters['don_vi_may_id'] === (string) $donViMay->id)>
                {{ $donViMay->ten_don_vi }}
              </option>
            @endforeach
          </select>
        </div>
        @include('content.shared._per-page-select', ['perPageColumnClass' => 'col-6 col-lg-2 filter-span-2'])

        <div class="col-12 col-lg-3 filter-span-3">
          <div class="d-flex gap-2 flex-wrap filter-actions">
            <button type="submit" class="btn btn-primary flex-fill flex-sm-grow-0">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('phan-bo-may.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
              <i class="icon-base bx bx-refresh me-1"></i> Làm mới
            </a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Ngày giao</th>
            <th>Mã đơn</th>
            <th>Mã KH</th>
            <th>Mã hàng</th>
            <th>Màu</th>
            <th>Size</th>
            <th>SL đặt</th>
            <th>SL cắt</th>
            <th>Đơn vị may</th>
            <th class="text-end">Số lượng giao may</th>
            <th style="width: 120px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($phanBoMays as $phanBoMay)
            <tr>
              <td>{{ $phanBoMays->firstItem() + $loop->index }}</td>
              <td>
                {{ $phanBoMay->ngay_phan_bo ? \Illuminate\Support\Carbon::parse($phanBoMay->ngay_phan_bo)->format('d/m/Y') : '-' }}
              </td>
              <td>{{ $phanBoMay->cat?->donHangChiTiet?->donHang?->ma_don ?? '-' }}</td>
              <td>{{ $phanBoMay->cat?->donHangChiTiet?->donHang?->ma_kh ?? '-' }}</td>
              <td>
                <strong>{{ $phanBoMay->cat?->matHang?->ma_hang ?? '-' }}</strong>
                <div class="text-muted small">{{ $phanBoMay->cat?->matHang?->ten_hang ?? '-' }}</div>
              </td>
              <td>{{ $phanBoMay->cat?->mau?->ten_mau ?? '-' }}</td>
              <td>{{ $phanBoMay->cat?->size?->ten_size ?? '-' }}</td>
              <td>{{ $formatPhanBoNumber($phanBoMay->cat?->donHangChiTiet?->so_luong_dat) }}</td>
              <td>{{ $formatPhanBoNumber($phanBoMay->source_total_cat ?? $phanBoMay->cat?->so_luong_cat) }}</td>
              <td>
                <strong>{{ $phanBoMay->donViMay?->ten_don_vi ?? '-' }}</strong>
              </td>
              <td class="text-end">{{ formatPhanBoNumber($phanBoMay->so_luong_giao) }}</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('PHAN_BO_MAY_EDIT'))
                    <a href="{{ route('phan-bo-may.edit', $phanBoMay) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('PHAN_BO_MAY_DELETE'))
                    <form action="{{ route('phan-bo-may.destroy', $phanBoMay) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa phân bổ may này?');">
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
              <td colspan="12" class="text-center py-4">Chưa có dữ liệu phân bổ may.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($phanBoMays->hasPages())
      <div class="card-footer">
        {{ $phanBoMays->links() }}
      </div>
    @endif
  </div>
@endsection
