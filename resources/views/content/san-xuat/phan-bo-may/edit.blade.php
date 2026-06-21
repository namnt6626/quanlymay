@extends('layouts/contentNavbarLayout')

@section('title', 'Cập nhật phân bổ may')

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('phan-bo-may-form');
      const numberInputs = Array.from(document.querySelectorAll('.js-number-format'));
      const catSelect = document.getElementById('cat_id');
      const donViMaySelect = document.getElementById('don_vi_may_id');
      const orderNumberText = document.getElementById('cat-order-number-text');
      const customerNumberText = document.getElementById('cat-customer-number-text');
      const productText = document.getElementById('cat-product-text');
      const colorText = document.getElementById('cat-color-text');
      const sizeText = document.getElementById('cat-size-text');
      const orderedQuantityText = document.getElementById('cat-ordered-quantity-text');
      const unitText = document.getElementById('cat-unit-text');
      const allocatedText = document.getElementById('cat-allocated-text');
      const remainingText = document.getElementById('cat-remaining-text');
      const totalText = document.getElementById('cat-total-text');

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

        if (!normalized) {
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

        if (!normalized) {
          return '';
        }

        return normalized.replace('.', ',');
      }

      function updateCatSummary() {
        if (!catSelect || !orderNumberText || !customerNumberText || !productText || !colorText || !sizeText || !
          orderedQuantityText || !unitText || !allocatedText || !remainingText || !totalText) {
          return;
        }

        const selectedOption = catSelect.options[catSelect.selectedIndex];
        const selectedUnit = donViMaySelect?.options[donViMaySelect.selectedIndex];
        const hasOrder = selectedOption?.dataset?.hasOrder === '1';
        const orderNumber = selectedOption?.dataset?.orderNumber || '-';
        const customerNumber = selectedOption?.dataset?.customerNumber || '-';
        const product = selectedOption?.dataset?.product || '-';
        const color = selectedOption?.dataset?.color || '-';
        const size = selectedOption?.dataset?.size || '-';
        const orderedQuantity = selectedOption?.dataset?.orderedQuantity || '';
        const cutQuantity = selectedOption?.dataset?.cutQuantity || '';
        const unit = selectedUnit?.dataset?.unit || '-';
        const allocated = selectedOption?.dataset?.allocated || '';
        const available = selectedOption?.dataset?.available || '';
        const total = selectedOption?.dataset?.total || '';

        orderNumberText.textContent = hasOrder ? orderNumber : '-';
        customerNumberText.textContent = hasOrder ? customerNumber : '-';
        productText.textContent = product;
        colorText.textContent = color;
        sizeText.textContent = size;
        orderedQuantityText.textContent = orderedQuantity ? formatDisplayNumber(orderedQuantity) : '-';
        unitText.textContent = unit;
        allocatedText.textContent = allocated ? formatDisplayNumber(allocated) + ' sản phẩm' : '-';
        remainingText.textContent = available ? formatDisplayNumber(available) + ' sản phẩm' : '-';
        totalText.textContent = cutQuantity ? formatDisplayNumber(cutQuantity) + ' sản phẩm' : total ?
          formatDisplayNumber(total) + ' sản phẩm' : '-';
      }

      numberInputs.forEach(function(input) {
        input.addEventListener('input', function() {
          input.value = input.value.replace(/[^\d.,]/g, '');
        });

        input.addEventListener('focus', function() {
          input.value = formatEditableNumber(input.value);
        });

        input.addEventListener('blur', function() {
          input.value = formatDisplayNumber(input.value);
        });

        input.value = formatDisplayNumber(input.value);
      });

      if (catSelect) {
        catSelect.addEventListener('change', updateCatSummary);
        updateCatSummary();
      }

      if (donViMaySelect) {
        donViMaySelect.addEventListener('change', updateCatSummary);
        updateCatSummary();
      }

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
      <h5 class="mb-0">Cập nhật phân bổ may</h5>
      <a href="{{ route('phan-bo-may.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('phan-bo-may.update', $phanBoMay) }}" method="POST" id="phan-bo-may-form">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="cat_id">Nguồn cắt</label>
            <select class="form-select" id="cat_id" disabled>
              <option value="">-- Chọn phiếu cắt --</option>
              @foreach ($cats as $cat)
                <option value="{{ $cat->id }}" data-has-order="{{ $cat->don_hang_chi_tiet_id ? '1' : '0' }}"
                  data-order-number="{{ $cat->donHangChiTiet?->donHang?->ma_don ?? '' }}"
                  data-customer-number="{{ $cat->donHangChiTiet?->donHang?->ma_kh ?? '' }}"
                  data-product="{{ ($cat->matHang?->ma_hang ?? '-') . ' - ' . ($cat->matHang?->ten_hang ?? '-') }}"
                  data-color="{{ $cat->mau?->ten_mau ?? '-' }}" data-size="{{ $cat->size?->ten_size ?? '-' }}"
                  data-ordered-quantity="{{ $cat->donHangChiTiet?->so_luong_dat ?? '' }}"
                  data-cut-quantity="{{ $cat->total_cat ?? 0 }}" data-total="{{ $cat->total_cat }}"
                  data-allocated="{{ $cat->total_phan_bo }}" data-available="{{ $cat->so_luong_con_lai }}"
                  @selected(old('cat_id', $selectedCatId) == $cat->id)>
                  @php
                    $sourceLabelParts = array_filter(
                        [
                            $cat->don_hang_chi_tiet_id ? $cat->donHangChiTiet?->donHang?->ma_don : null,
                            $cat->don_hang_chi_tiet_id ? $cat->donHangChiTiet?->donHang?->ma_kh : null,
                            $cat->matHang?->ma_hang ?? '-',
                            $cat->mau?->ten_mau ?? '-',
                            $cat->size?->ten_size ?? '-',
                            $cat->don_hang_chi_tiet_id
                                ? 'SL đặt: ' . $formatPhanBoNumber($cat->donHangChiTiet?->so_luong_dat)
                                : null,
                            'SL cắt: ' . $formatPhanBoNumber($cat->total_cat ?? 0),
                        ],
                        fn($value) => $value !== null && $value !== '',
                    );
                  @endphp
                  {{ implode(' - ', $sourceLabelParts) }}
                </option>
              @endforeach
            </select>
            <div class="border rounded p-3 mt-3 bg-light">
              <div class="row g-3">
                <div class="col-sm-6 col-xl-4">
                  <div class="text-muted small">Mã đơn</div>
                  <div class="fw-semibold" id="cat-order-number-text">-</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                  <div class="text-muted small">Mã KH</div>
                  <div class="fw-semibold" id="cat-customer-number-text">-</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                  <div class="text-muted small">Mã hàng</div>
                  <div class="fw-semibold" id="cat-product-text">-</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                  <div class="text-muted small">Màu</div>
                  <div class="fw-semibold" id="cat-color-text">-</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                  <div class="text-muted small">Size</div>
                  <div class="fw-semibold" id="cat-size-text">-</div>
                </div>
                <div class="col-sm-6 col-xl-4">
                  <div class="text-muted small">SL đặt</div>
                  <div class="fw-semibold" id="cat-ordered-quantity-text">-</div>
                </div>
              </div>

              <div class="mt-3 mb-3">
                <div class="text-muted small">Đơn vị may</div>
                <div class="fw-semibold" id="cat-unit-text">-</div>
              </div>

              <div class="row g-3 text-center">
                <div class="col-sm-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">SL cắt</div>
                    <div class="fw-semibold" id="cat-total-text">-</div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Đã phân bổ</div>
                    <div class="fw-semibold" id="cat-allocated-text">-</div>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Còn lại</div>
                    <div class="fw-semibold" id="cat-remaining-text">-</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="don_vi_may_id">Đơn vị may <span class="text-danger">*</span></label>
            <select class="form-select @error('don_vi_may_id') is-invalid @enderror" id="don_vi_may_id"
              name="don_vi_may_id" required>
              <option value="">-- Chọn đơn vị may --</option>
              @foreach ($donViMays as $donViMay)
                <option value="{{ $donViMay->id }}" data-unit="{{ $donViMay->ten_don_vi }}"
                  @selected(old('don_vi_may_id', $phanBoMay->don_vi_may_id) == $donViMay->id)>
                  {{ $donViMay->ma_don_vi }} - {{ $donViMay->ten_don_vi }}
                </option>
              @endforeach
            </select>
            @error('don_vi_may_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="ngay_phan_bo">Ngày phân bổ <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('ngay_phan_bo') is-invalid @enderror" id="ngay_phan_bo"
              name="ngay_phan_bo" value="{{ old('ngay_phan_bo', optional($phanBoMay->ngay_phan_bo)->format('Y-m-d')) }}"
              required>
            @error('ngay_phan_bo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="so_luong_giao">Số lượng giao <span class="text-danger">*</span></label>
            <input type="text" inputmode="decimal" autocomplete="off"
              class="form-control js-number-format @error('so_luong_giao') is-invalid @enderror" id="so_luong_giao"
              name="so_luong_giao" value="{{ formatPhanBoNumber(old('so_luong_giao', $phanBoMay->so_luong_giao)) }}"
              required>
            @error('so_luong_giao')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="4">{{ old('ghi_chu', $phanBoMay->ghi_chu) }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Cập nhật
              </button>
              <a href="{{ route('phan-bo-may.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
