@extends('layouts/contentNavbarLayout')

@section('title', 'Cập nhật lần cắt')

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('cat-form');
      const donHangChiTietSelect = document.getElementById('don_hang_chi_tiet_id');
      const matHangSelect = document.getElementById('mat_hang_id');
      const mauSelect = document.getElementById('mau_id');
      const sizeSelect = document.getElementById('size_id');
      const numberInputs = Array.from(document.querySelectorAll('.js-number-format'));
      const soLuongInput = document.getElementById('so_luong_cat');
      const dinhMucInput = document.getElementById('dinh_muc');
      const previewInput = document.getElementById('vai_tieu_hao_display');
      const donHangMaDonInput = document.getElementById('don_hang_ma_don');
      const donHangMaKhInput = document.getElementById('don_hang_ma_kh');
      const donHangSoLuongDatInput = document.getElementById('don_hang_so_luong_dat');
      const donHangHanGiaoInput = document.getElementById('don_hang_han_giao');
      const donHangKenhBanInput = document.getElementById('don_hang_kenh_ban');

      function normalizeNumber(value) {
        let text = String(value || '').trim();

        if (!text) {
          return '';
        }

        text = text.replace(/\s+/g, '');

        const commaCount = (text.match(/,/g) || []).length;
        const dotCount = (text.match(/\./g) || []).length;

        if (commaCount > 0 && dotCount > 0) {
          const decimalSeparator = text.lastIndexOf(',') > text.lastIndexOf('.') ? ',' : '.';
          const thousandSeparator = decimalSeparator === ',' ? '.' : ',';
          text = text.split(thousandSeparator).join('');
          text = text.replace(decimalSeparator, '.');
        } else if (commaCount > 0) {
          const parts = text.split(',');
          if (commaCount === 1 && parts[parts.length - 1].length !== 3) {
            text = text.replace(',', '.');
          } else {
            text = text.split(',').join('');
          }
        } else if (dotCount > 0) {
          const parts = text.split('.');
          if (!(dotCount === 1 && parts[parts.length - 1].length !== 3)) {
            text = text.split('.').join('');
          }
        }

        text = text.replace(/[^\d.\-]/g, '');

        const firstDotIndex = text.indexOf('.');
        if (firstDotIndex !== -1) {
          text = text.slice(0, firstDotIndex + 1) + text.slice(firstDotIndex + 1).replace(/\./g, '');
        }

        return text;
      }

      function formatDisplayNumber(value) {
        const normalized = normalizeNumber(value);

        if (normalized === '') {
          return '';
        }

        const number = Number(normalized);

        if (Number.isNaN(number)) {
          return '';
        }

        return new Intl.NumberFormat('de-DE', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 4,
        }).format(number);
      }

      function formatEditableNumber(value) {
        const normalized = normalizeNumber(value);

        if (normalized === '') {
          return '';
        }

        return normalized.replace('.', ',');
      }

      function updatePreview() {
        if (!soLuongInput || !dinhMucInput || !previewInput) {
          return;
        }

        const soLuong = Number(normalizeNumber(soLuongInput.value) || 0);
        const dinhMuc = Number(normalizeNumber(dinhMucInput.value) || 0);

        if (!soLuong || !dinhMuc) {
          previewInput.value = '-';
          return;
        }

        previewInput.value = formatDisplayNumber(String(soLuong * dinhMuc)) + ' m';
      }

      function clearDonHangInfo() {
        if (donHangMaDonInput) {
          donHangMaDonInput.value = '-';
        }
        if (donHangMaKhInput) {
          donHangMaKhInput.value = '-';
        }
        if (donHangSoLuongDatInput) {
          donHangSoLuongDatInput.value = '-';
        }
        if (donHangHanGiaoInput) {
          donHangHanGiaoInput.value = '-';
        }
        if (donHangKenhBanInput) {
          donHangKenhBanInput.value = '-';
        }
      }

      function applyDonHangSelection() {
        if (!donHangChiTietSelect) {
          return;
        }

        const selectedOption = donHangChiTietSelect.selectedOptions[0];

        if (!selectedOption || !selectedOption.value) {
          clearDonHangInfo();
          return;
        }

        if (matHangSelect) {
          matHangSelect.value = selectedOption.dataset.matHangId || '';
        }
        if (mauSelect) {
          mauSelect.value = selectedOption.dataset.mauId || '';
        }
        if (sizeSelect) {
          sizeSelect.value = selectedOption.dataset.sizeId || '';
        }
        if (donHangMaDonInput) {
          donHangMaDonInput.value = selectedOption.dataset.maDon || '-';
        }
        if (donHangMaKhInput) {
          donHangMaKhInput.value = selectedOption.dataset.maKh || '-';
        }
        if (donHangSoLuongDatInput) {
          donHangSoLuongDatInput.value = selectedOption.dataset.soLuongDat || '-';
        }
        if (donHangHanGiaoInput) {
          donHangHanGiaoInput.value = selectedOption.dataset.hanGiao || '-';
        }
        if (donHangKenhBanInput) {
          donHangKenhBanInput.value = selectedOption.dataset.kenhBan || '-';
        }
      }

      numberInputs.forEach(function(input) {
        input.addEventListener('input', function() {
          input.value = input.value.replace(/[^\d.,]/g, '');
          updatePreview();
        });

        input.addEventListener('focus', function() {
          input.value = formatEditableNumber(input.value);
        });

        input.addEventListener('blur', function() {
          input.value = formatDisplayNumber(input.value);
          updatePreview();
        });

        input.value = formatDisplayNumber(input.value);
      });

      if (donHangChiTietSelect) {
        applyDonHangSelection();
        donHangChiTietSelect.addEventListener('change', applyDonHangSelection);
      }

      updatePreview();

      if (form) {
        form.addEventListener('submit', function() {
          numberInputs.forEach(function(input) {
            input.value = normalizeNumber(input.value);
          });
        });
      }
    });
  </script>
@endsection

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
      <h5 class="mb-0">Cập nhật lần cắt</h5>
      <a href="{{ route('cat.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('cat.update', $cat) }}" method="POST" id="cat-form">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <div class="col-12">
            <label class="form-label" for="don_hang_chi_tiet_id">Chọn dòng đơn hàng nếu có</label>
            <select class="form-select @error('don_hang_chi_tiet_id') is-invalid @enderror" id="don_hang_chi_tiet_id"
              name="don_hang_chi_tiet_id">
              <option value="">-- Không chọn đơn hàng --</option>
              @foreach ($donHangChiTiets as $donHangChiTiet)
                @php
                  $donHang = $donHangChiTiet->donHang;
                  $label = implode(
                      ' - ',
                      array_filter(
                          [
                              $donHang?->ma_don,
                              $donHang?->ma_kh,
                              $donHangChiTiet->matHang?->ten_hang,
                              $donHangChiTiet->mau?->ten_mau,
                              $donHangChiTiet->size?->ten_size,
                              'SL đặt: ' . $formatCatNumber($donHangChiTiet->so_luong_dat),
                              $donHang?->han_giao ? 'Hạn giao: ' . $donHang->han_giao->format('d/m/Y') : null,
                          ],
                          fn($value) => $value !== null && $value !== '',
                      ),
                  );
                @endphp
                <option value="{{ $donHangChiTiet->id }}" @selected(old('don_hang_chi_tiet_id', $cat->don_hang_chi_tiet_id) == $donHangChiTiet->id)
                  data-ma-don="{{ $donHang?->ma_don ?? '' }}" data-ma-kh="{{ $donHang?->ma_kh ?? '' }}"
                  data-so-luong-dat="{{ $formatCatNumber($donHangChiTiet->so_luong_dat) }}"
                  data-han-giao="{{ $donHang?->han_giao ? $donHang->han_giao->format('d/m/Y') : '-' }}"
                  data-kenh-ban="{{ $donHang?->kenh_ban ?? '-' }}" data-mat-hang-id="{{ $donHangChiTiet->mat_hang_id }}"
                  data-mau-id="{{ $donHangChiTiet->mau_id }}" data-size-id="{{ $donHangChiTiet->size_id }}">
                  {{ $label !== '' ? $label : 'Dòng đơn hàng #' . $donHangChiTiet->id }}
                </option>
              @endforeach
            </select>
            @error('don_hang_chi_tiet_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Mã đơn</label>
            <input type="text" class="form-control" id="don_hang_ma_don" value="-" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label">Mã KH</label>
            <input type="text" class="form-control" id="don_hang_ma_kh" value="-" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label">SL đặt</label>
            <input type="text" class="form-control" id="don_hang_so_luong_dat" value="-" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label">Hạn giao</label>
            <input type="text" class="form-control" id="don_hang_han_giao" value="-" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label">Kênh bán</label>
            <input type="text" class="form-control" id="don_hang_kenh_ban" value="-" readonly>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="ngay_cat">Ngày cắt <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('ngay_cat') is-invalid @enderror" id="ngay_cat"
              name="ngay_cat" value="{{ old('ngay_cat', optional($cat->ngay_cat)->format('Y-m-d')) }}" required>
            @error('ngay_cat')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="mat_hang_id">Mặt hàng <span class="text-danger">*</span></label>
            <select class="form-select @error('mat_hang_id') is-invalid @enderror" id="mat_hang_id" name="mat_hang_id"
              required>
              <option value="">-- Chọn mặt hàng --</option>
              @foreach ($matHangs as $matHang)
                <option value="{{ $matHang->id }}" @selected(old('mat_hang_id', $cat->mat_hang_id) == $matHang->id)>
                  {{ $matHang->ten_hang }}
                </option>
              @endforeach
            </select>
            @error('mat_hang_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="mau_id">Màu <span class="text-danger">*</span></label>
            <select class="form-select @error('mau_id') is-invalid @enderror" id="mau_id" name="mau_id" required>
              <option value="">-- Chọn màu --</option>
              @foreach ($maus as $mau)
                <option value="{{ $mau->id }}" @selected(old('mau_id', $cat->mau_id) == $mau->id)>
                  {{ $mau->ten_mau }}
                </option>
              @endforeach
            </select>
            @error('mau_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="size_id">Size <span class="text-danger">*</span></label>
            <select class="form-select @error('size_id') is-invalid @enderror" id="size_id" name="size_id" required>
              <option value="">-- Chọn size --</option>
              @foreach ($sizes as $size)
                <option value="{{ $size->id }}" @selected(old('size_id', $cat->size_id) == $size->id)>
                  {{ $size->ten_size }}
                </option>
              @endforeach
            </select>
            @error('size_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="ban_cat_ten">Bàn cắt <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('ban_cat_ten') is-invalid @enderror" id="ban_cat_ten"
              name="ban_cat_ten" value="{{ old('ban_cat_ten', $cat->banCat?->ten_ban) }}" maxlength="255" required>
            @error('ban_cat_ten')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="don_vi_cat_id">Đơn vị cắt <span class="text-danger">*</span></label>
            <select class="form-select @error('don_vi_cat_id') is-invalid @enderror" id="don_vi_cat_id"
              name="don_vi_cat_id" required>
              <option value="">-- Chọn đơn vị cắt --</option>
              @foreach ($donViCats as $donViCat)
                <option value="{{ $donViCat->id }}" @selected(old('don_vi_cat_id', $cat->don_vi_cat_id) == $donViCat->id)>
                  {{ $donViCat->ten_don_vi }}
                </option>
              @endforeach
            </select>
            @error('don_vi_cat_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="so_luong_cat">Số lượng <span class="text-danger">*</span></label>
            @php
              $formatCatNumber = function ($value) {
                  if ($value === null || $value === '') {
                      return '';
                  }

                  $formatted = number_format((float) $value, 4, ',', '.');

                  return rtrim(rtrim($formatted, '0'), ',');
              };

              $soLuongFormatted = $formatCatNumber(old('so_luong_cat', $cat->so_luong_cat));
            @endphp
            <input type="text" inputmode="decimal" autocomplete="off"
              class="form-control js-number-format @error('so_luong_cat') is-invalid @enderror" id="so_luong_cat"
              name="so_luong_cat" value="{{ $soLuongFormatted }}" required>
            @error('so_luong_cat')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="dinh_muc">Định mức <span class="text-danger">*</span></label>
            <div class="input-group">
              @php
                $dinhMucFormatted = $formatCatNumber(old('dinh_muc', $cat->dinh_muc));
              @endphp
              <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control js-number-format @error('dinh_muc') is-invalid @enderror" id="dinh_muc"
                name="dinh_muc" value="{{ $dinhMucFormatted }}" required>
              <span class="input-group-text">m</span>
              @error('dinh_muc')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="vai_tieu_hao_display">Vải tiêu hao</label>
            @php
              $soLuongPreview = old('so_luong_cat', $cat->so_luong_cat);
              $dinhMucPreview = old('dinh_muc', $cat->dinh_muc);
              $vaiTieuHaoPreview =
                  is_numeric($soLuongPreview) && is_numeric($dinhMucPreview)
                      ? (float) $soLuongPreview * (float) $dinhMucPreview
                      : null;
            @endphp
            <input type="text" class="form-control" id="vai_tieu_hao_display"
              value="{{ $vaiTieuHaoPreview !== null ? $formatCatNumber($vaiTieuHaoPreview) . ' m' : '-' }}" readonly>
            <div class="form-text">Tự động tính theo số lượng × định mức.</div>
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="4">{{ old('ghi_chu', $cat->ghi_chu) }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Cập nhật
              </button>
              <a href="{{ route('cat.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
