@extends('layouts/contentNavbarLayout')

@section('title', 'Cập nhật nhập kho')

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('nhap-kho-form');
      const numberInputs = Array.from(document.querySelectorAll('.js-number-format'));
      const qcSelect = document.getElementById('qc_id');
      const orderNumberText = document.getElementById('qc-order-number-text');
      const customerNumberText = document.getElementById('qc-customer-number-text');
      const productCodeText = document.getElementById('qc-product-code-text');
      const productNameText = document.getElementById('qc-product-name-text');
      const colorText = document.getElementById('qc-color-text');
      const sizeText = document.getElementById('qc-size-text');
      const unitText = document.getElementById('qc-unit-text');
      const orderQuantityText = document.getElementById('qc-order-quantity-text');
      const qcPassedText = document.getElementById('qc-passed-text');
      const importedText = document.getElementById('qc-imported-text');
      const remainingText = document.getElementById('qc-remaining-text');

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

      function updateQcInfo() {
        if (!qcSelect) {
          return;
        }

        const selectedOption = qcSelect.options[qcSelect.selectedIndex];
        const hasOrder = selectedOption?.dataset?.hasOrder === '1';
        const orderNumber = selectedOption?.dataset?.orderNumber || '-';
        const customerNumber = selectedOption?.dataset?.customerNumber || '-';
        const productCode = selectedOption?.dataset?.productCode || '-';
        const productName = selectedOption?.dataset?.productName || '-';
        const color = selectedOption?.dataset?.color || '-';
        const size = selectedOption?.dataset?.size || '-';
        const unitName = selectedOption?.dataset?.unitName || '-';
        const orderQuantity = selectedOption?.dataset?.orderQuantity || '';
        const qcPassed = selectedOption?.dataset?.qcPassed || '';
        const imported = selectedOption?.dataset?.imported || '';
        const remaining = selectedOption?.dataset?.remaining || '';

        if (orderNumberText) orderNumberText.textContent = hasOrder ? orderNumber : '-';
        if (customerNumberText) customerNumberText.textContent = hasOrder ? customerNumber : '-';
        if (productCodeText) productCodeText.textContent = productCode;
        if (productNameText) productNameText.textContent = productName;
        if (colorText) colorText.textContent = color;
        if (sizeText) sizeText.textContent = size;
        if (unitText) unitText.textContent = unitName;
        if (orderQuantityText) orderQuantityText.textContent = hasOrder && orderQuantity ? formatDisplayNumber(orderQuantity) : '-';
        if (qcPassedText) qcPassedText.textContent = qcPassed ? formatDisplayNumber(qcPassed) + ' sản phẩm' : '-';
        if (importedText) importedText.textContent = imported ? formatDisplayNumber(imported) + ' sản phẩm' : '-';
        if (remainingText) remainingText.textContent = remaining ? formatDisplayNumber(remaining) + ' sản phẩm' : '-';
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

      if (qcSelect) {
        qcSelect.addEventListener('change', updateQcInfo);
        updateQcInfo();
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
      <h5 class="mb-0">Cập nhật nhập kho</h5>
      <a href="{{ route('nhap-kho.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('nhap-kho.update', $nhapKho) }}" method="POST" id="nhap-kho-form">
        @csrf
        @method('PATCH')

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="qc_id">Nguồn QC <span class="text-danger">*</span></label>
            <select class="form-select @error('qc_id') is-invalid @enderror" id="qc_id" name="qc_id" required>
              <option value="">-- Chọn nguồn QC --</option>
              @foreach ($qcs as $qc)
                @php
                  $isOrderSource = (bool) $qc->source_has_order;
                  $sourceLabelParts = array_filter(
                      [
                          $isOrderSource ? $qc->source_order_number : null,
                          $isOrderSource ? $qc->source_customer_number : null,
                          $qc->source_product_code,
                          $qc->source_color,
                          $qc->source_size,
                          $qc->source_unit_name,
                          $isOrderSource && $qc->source_order_quantity !== null
                              ? 'SL đặt: ' . $formatPhanBoNumber($qc->source_order_quantity)
                              : null,
                          'QC đạt: ' . $formatPhanBoNumber($qc->source_total_qc),
                      ],
                      fn ($value) => $value !== null && $value !== '',
                  );

                  $sourceLabel = implode(' - ', $sourceLabelParts);
                @endphp
                <option value="{{ $qc->id }}"
                  data-has-order="{{ $isOrderSource ? '1' : '0' }}"
                  data-order-number="{{ $qc->source_order_number ?? '' }}"
                  data-customer-number="{{ $qc->source_customer_number ?? '' }}"
                  data-product-code="{{ $qc->source_product_code ?? '-' }}"
                  data-product-name="{{ $qc->source_product_name ?? '-' }}"
                  data-color="{{ $qc->source_color ?? '-' }}"
                  data-size="{{ $qc->source_size ?? '-' }}"
                  data-unit-name="{{ $qc->source_unit_name ?? '-' }}"
                  data-order-quantity="{{ $qc->source_order_quantity ?? '' }}"
                  data-qc-passed="{{ $qc->source_total_qc ?? '' }}"
                  data-imported="{{ $qc->source_total_imported ?? '' }}"
                  data-remaining="{{ $qc->source_total_remaining ?? '' }}" @selected(old('qc_id', $selectedQcId ?? $nhapKho->qc_id) == $qc->id)>
                  {{ $sourceLabel !== '' ? $sourceLabel : 'Nguồn QC #' . $qc->id }}
                </option>
              @endforeach
            </select>
            @error('qc_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="border rounded p-3 bg-light">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="text-muted small">Mã đơn</div>
                  <div class="fw-semibold" id="qc-order-number-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Mã KH</div>
                  <div class="fw-semibold" id="qc-customer-number-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Mã hàng</div>
                  <div class="fw-semibold" id="qc-product-code-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Tên hàng</div>
                  <div class="fw-semibold" id="qc-product-name-text">-</div>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="text-muted small">Màu</div>
                  <div class="fw-semibold" id="qc-color-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Size</div>
                  <div class="fw-semibold" id="qc-size-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Đơn vị may</div>
                  <div class="fw-semibold" id="qc-unit-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">SL đặt</div>
                  <div class="fw-semibold" id="qc-order-quantity-text">-</div>
                </div>
              </div>
              <div class="row g-3 text-center">
                <div class="col-12 col-md-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">QC đạt</div>
                    <div class="fw-semibold" id="qc-passed-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Đã nhập kho</div>
                    <div class="fw-semibold" id="qc-imported-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Còn lại</div>
                    <div class="fw-semibold" id="qc-remaining-text">-</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="row g-3 align-items-start">
              <div class="col-12 col-md-6">
                <label class="form-label" for="ngay_nhap">Ngày nhập <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('ngay_nhap') is-invalid @enderror" id="ngay_nhap"
                  name="ngay_nhap" value="{{ old('ngay_nhap', optional($nhapKho->ngay_nhap)->format('Y-m-d')) }}"
                  required>
                @error('ngay_nhap')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="so_luong_nhap">SL nhập kho <span class="text-danger">*</span></label>
                <input type="text" inputmode="decimal" autocomplete="off"
                  class="form-control js-number-format @error('so_luong_nhap') is-invalid @enderror"
                  id="so_luong_nhap" name="so_luong_nhap"
                  value="{{ old('so_luong_nhap') !== null && old('so_luong_nhap') !== '' ? formatPhanBoNumber(old('so_luong_nhap')) : formatPhanBoNumber($nhapKho->so_luong_nhap) }}"
                  required>
                @error('so_luong_nhap')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="4">{{ old('ghi_chu', $nhapKho->ghi_chu) }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Cập nhật
              </button>
              <a href="{{ route('nhap-kho.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
