@extends('layouts/contentNavbarLayout')

@section('title', 'Cập nhật xuất kho')

@section('page-style')
  <style>
    @media (max-width: 575.98px) {
      .xuat-edit-source-card {
        background-color: var(--bs-card-bg, #fff) !important;
      }

      .xuat-edit-source-card .row.g-3 > [class*="col-"] {
        width: 50%;
      }

      .xuat-edit-source-card .row.g-3 > [class*="col-"] > .text-muted.small {
        font-weight: 600;
        opacity: 0.85;
      }

      #so_luong_xuat {
        height: 48px;
        padding: 0.65rem 0.85rem;
        font-size: 1.05rem;
        text-align: right;
      }
    }
  </style>
@endsection

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('xuat-kho-form');
      const numberInputs = Array.from(document.querySelectorAll('.js-number-format'));
      const nhapKhoSelect = document.getElementById('nhap_kho_id');
      const kenhBanInput = document.getElementById('kenh_ban');
      const orderNumberText = document.getElementById('xuat-order-number-text');
      const customerNumberText = document.getElementById('xuat-customer-number-text');
      const productCodeText = document.getElementById('xuat-product-code-text');
      const productNameText = document.getElementById('xuat-product-name-text');
      const colorText = document.getElementById('xuat-color-text');
      const sizeText = document.getElementById('xuat-size-text');
      const orderQuantityText = document.getElementById('xuat-order-quantity-text');
      const importedText = document.getElementById('xuat-imported-text');
      const exportedText = document.getElementById('xuat-exported-text');
      const remainingText = document.getElementById('xuat-remaining-text');
      const defaultChannelText = document.getElementById('xuat-default-channel-text');

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

      function updateNhapKhoInfo() {
        if (!nhapKhoSelect) {
          return;
        }

        const selectedOption = nhapKhoSelect.options[nhapKhoSelect.selectedIndex];
        const hasOrder = selectedOption?.dataset?.hasOrder === '1';
        const orderNumber = selectedOption?.dataset?.orderNumber || '-';
        const customerNumber = selectedOption?.dataset?.customerNumber || '-';
        const productCode = selectedOption?.dataset?.productCode || '-';
        const productName = selectedOption?.dataset?.productName || '-';
        const color = selectedOption?.dataset?.color || '-';
        const size = selectedOption?.dataset?.size || '-';
        const orderQuantity = selectedOption?.dataset?.orderQuantity || '';
        const imported = selectedOption?.dataset?.imported || '';
        const exported = selectedOption?.dataset?.exported || '';
        const remaining = selectedOption?.dataset?.remaining || '';
        const kenhBan = selectedOption?.dataset?.kenhBan || '';

        if (orderNumberText) orderNumberText.textContent = hasOrder ? orderNumber : '-';
        if (customerNumberText) customerNumberText.textContent = hasOrder ? customerNumber : '-';
        if (productCodeText) productCodeText.textContent = productCode;
        if (productNameText) productNameText.textContent = productName;
        if (colorText) colorText.textContent = color;
        if (sizeText) sizeText.textContent = size;
        if (orderQuantityText) orderQuantityText.textContent = hasOrder && orderQuantity ? formatDisplayNumber(orderQuantity) : '-';
        if (importedText) importedText.textContent = imported ? formatDisplayNumber(imported) + ' sản phẩm' : '-';
        if (exportedText) exportedText.textContent = exported ? formatDisplayNumber(exported) + ' sản phẩm' : '-';
        if (remainingText) remainingText.textContent = remaining ? formatDisplayNumber(remaining) + ' sản phẩm' : '-';
        if (defaultChannelText) defaultChannelText.textContent = kenhBan || '-';

        if (kenhBanInput && kenhBan) {
          kenhBanInput.value = kenhBan;
        }
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

      if (nhapKhoSelect) {
        nhapKhoSelect.addEventListener('change', updateNhapKhoInfo);
        updateNhapKhoInfo();
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
      <h5 class="mb-0">Cập nhật xuất kho</h5>
      <a href="{{ route('xuat-kho.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('xuat-kho.update', $phieuXuatKho) }}" method="POST" id="xuat-kho-form">
        @csrf
        @method('PATCH')

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="nhap_kho_id">Nguồn nhập kho <span class="text-danger">*</span></label>
            <select class="form-select @error('nhap_kho_id') is-invalid @enderror" id="nhap_kho_id"
              name="nhap_kho_id" required>
              <option value="">-- Chọn nguồn nhập kho --</option>
              @foreach ($nhapKhos as $nhapKho)
                @php
                  $isOrderSource = (bool) $nhapKho->source_has_order;
                  $sourceLabelParts = array_filter(
                      [
                          $isOrderSource ? $nhapKho->source_order_number : null,
                          $isOrderSource ? $nhapKho->source_customer_number : null,
                          $nhapKho->source_product_code,
                          $nhapKho->source_color,
                          $nhapKho->source_size,
                          $isOrderSource && $nhapKho->source_order_quantity !== null
                              ? 'SL đặt: ' . $formatPhanBoNumber($nhapKho->source_order_quantity)
                              : null,
                          'Nhập kho: ' . $formatPhanBoNumber($nhapKho->source_total_imported),
                          'Đã xuất: ' . $formatPhanBoNumber($nhapKho->source_total_exported),
                          'Còn: ' . $formatPhanBoNumber($nhapKho->source_total_remaining),
                      ],
                      fn ($value) => $value !== null && $value !== '',
                  );

                  $sourceLabel = implode(' - ', $sourceLabelParts);
                @endphp
                <option value="{{ $nhapKho->id }}"
                  data-has-order="{{ $isOrderSource ? '1' : '0' }}"
                  data-order-number="{{ $nhapKho->source_order_number ?? '' }}"
                  data-customer-number="{{ $nhapKho->source_customer_number ?? '' }}"
                  data-product-code="{{ $nhapKho->source_product_code ?? '-' }}"
                  data-product-name="{{ $nhapKho->source_product_name ?? '-' }}"
                  data-color="{{ $nhapKho->source_color ?? '-' }}"
                  data-size="{{ $nhapKho->source_size ?? '-' }}"
                  data-order-quantity="{{ $nhapKho->source_order_quantity ?? '' }}"
                  data-imported="{{ $nhapKho->source_total_imported ?? '' }}"
                  data-exported="{{ $nhapKho->source_total_exported ?? '' }}"
                  data-remaining="{{ $nhapKho->source_total_remaining ?? '' }}"
                  data-kenh-ban="{{ $nhapKho->source_kenh_ban ?? '' }}" @selected(old('nhap_kho_id', $selectedNhapKhoId ?? $chiTiet?->nhap_kho_id) == $nhapKho->id)>
                  {{ $sourceLabel !== '' ? $sourceLabel : 'Nguồn nhập kho #' . $nhapKho->id }}
                </option>
              @endforeach
            </select>
            @error('nhap_kho_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="border rounded p-3 bg-light xuat-edit-source-card">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="text-muted small">Mã đơn</div>
                  <div class="fw-semibold" id="xuat-order-number-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Mã KH</div>
                  <div class="fw-semibold" id="xuat-customer-number-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Mã hàng</div>
                  <div class="fw-semibold" id="xuat-product-code-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Tên hàng</div>
                  <div class="fw-semibold" id="xuat-product-name-text">-</div>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="text-muted small">Màu</div>
                  <div class="fw-semibold" id="xuat-color-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Size</div>
                  <div class="fw-semibold" id="xuat-size-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">SL đặt</div>
                  <div class="fw-semibold" id="xuat-order-quantity-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Kênh bán mặc định</div>
                  <div class="fw-semibold" id="xuat-default-channel-text">-</div>
                </div>
              </div>
              <div class="row g-3 text-center">
                <div class="col-12 col-md-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Nhập kho</div>
                    <div class="fw-semibold" id="xuat-imported-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Đã xuất</div>
                    <div class="fw-semibold" id="xuat-exported-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Còn lại</div>
                    <div class="fw-semibold" id="xuat-remaining-text">-</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="row g-3 align-items-start">
              <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="so_phieu">Số phiếu <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('so_phieu') is-invalid @enderror" id="so_phieu"
                  name="so_phieu" value="{{ old('so_phieu', $phieuXuatKho->so_phieu) }}" required>
                @error('so_phieu')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="ngay_xuat">Ngày xuất <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('ngay_xuat') is-invalid @enderror" id="ngay_xuat"
                  name="ngay_xuat" value="{{ old('ngay_xuat', optional($phieuXuatKho->ngay_xuat)->format('Y-m-d')) }}" required>
                @error('ngay_xuat')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="kenh_ban">Kênh bán <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('kenh_ban') is-invalid @enderror" id="kenh_ban"
                  name="kenh_ban" value="{{ old('kenh_ban', $phieuXuatKho->kenh_ban) }}" required>
                @error('kenh_ban')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="so_luong_xuat">SL xuất <span class="text-danger">*</span></label>
                <input type="text" inputmode="decimal" autocomplete="off"
                  class="form-control js-number-format @error('so_luong_xuat') is-invalid @enderror"
                  id="so_luong_xuat" name="so_luong_xuat"
                  value="{{ old('so_luong_xuat') !== null && old('so_luong_xuat') !== '' ? formatPhanBoNumber(old('so_luong_xuat')) : formatPhanBoNumber($chiTiet?->so_luong_xuat) }}"
                  required>
                @error('so_luong_xuat')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="4">{{ old('ghi_chu', $phieuXuatKho->ghi_chu) }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Cập nhật
              </button>
              <a href="{{ route('xuat-kho.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
