@extends('layouts/contentNavbarLayout')

@section('title', 'Cập nhật QC')

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('qc-form');
      const numberInputs = Array.from(document.querySelectorAll('.js-number-format'));
      const phanBoSelect = document.getElementById('phan_bo_may_id');
      const orderNumberText = document.getElementById('phanbo-order-number-text');
      const customerNumberText = document.getElementById('phanbo-customer-number-text');
      const productCodeText = document.getElementById('phanbo-product-code-text');
      const productNameText = document.getElementById('phanbo-product-name-text');
      const colorText = document.getElementById('phanbo-color-text');
      const sizeText = document.getElementById('phanbo-size-text');
      const orderQuantityText = document.getElementById('phanbo-order-quantity-text');
      const unitText = document.getElementById('phanbo-unit-text');
      const cutText = document.getElementById('phanbo-cut-text');
      const deliveredText = document.getElementById('phanbo-delivered-text');
      const qcDoneText = document.getElementById('phanbo-qc-done-text');
      const remainingText = document.getElementById('phanbo-remaining-text');
      const totalQcInput = document.getElementById('so_luong_qc');
      const totalQcText = document.getElementById('qc-total-text');
      const qcPartInputs = [
        document.getElementById('so_luong_dat'),
        document.getElementById('so_luong_loi'),
        document.getElementById('so_luong_hong')
      ].filter(Boolean);

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

      function updatePhanBoInfo() {
        if (!phanBoSelect) {
          return;
        }

        const selectedOption = phanBoSelect.options[phanBoSelect.selectedIndex];
        const hasOrder = selectedOption?.dataset?.hasOrder === '1';
        const orderNumber = selectedOption?.dataset?.orderNumber || '-';
        const customerNumber = selectedOption?.dataset?.customerNumber || '-';
        const productCode = selectedOption?.dataset?.productCode || '-';
        const productName = selectedOption?.dataset?.productName || '-';
        const color = selectedOption?.dataset?.color || '-';
        const size = selectedOption?.dataset?.size || '-';
        const orderQuantity = selectedOption?.dataset?.orderQuantity || '';
        const unitName = selectedOption?.dataset?.unitName || '-';
        const cut = selectedOption?.dataset?.cut || '';
        const delivered = selectedOption?.dataset?.delivered || '';
        const qcDone = selectedOption?.dataset?.qcDone || '';
        const remaining = selectedOption?.dataset?.remaining || '';

        if (orderNumberText) orderNumberText.textContent = hasOrder ? orderNumber : '-';
        if (customerNumberText) customerNumberText.textContent = hasOrder ? customerNumber : '-';
        if (productCodeText) productCodeText.textContent = productCode;
        if (productNameText) productNameText.textContent = productName;
        if (colorText) colorText.textContent = color;
        if (sizeText) sizeText.textContent = size;
        if (orderQuantityText) orderQuantityText.textContent = hasOrder && orderQuantity ? formatDisplayNumber(
          orderQuantity) : '-';
        if (unitText) unitText.textContent = unitName;
        if (cutText) cutText.textContent = cut ? formatDisplayNumber(cut) + ' sản phẩm' : '-';
        if (deliveredText) deliveredText.textContent = delivered ? formatDisplayNumber(delivered) + ' sản phẩm' : '-';
        if (qcDoneText) qcDoneText.textContent = qcDone ? formatDisplayNumber(qcDone) + ' sản phẩm' : '-';
        if (remainingText) remainingText.textContent = remaining ? formatDisplayNumber(remaining) + ' sản phẩm' : '-';
      }

      function calculateTotalQc() {
        const total = qcPartInputs.reduce(function(sum, input) {
          const value = Number(normalizeNumber(input.value) || 0);

          return sum + (Number.isNaN(value) ? 0 : value);
        }, 0);

        if (totalQcInput) {
          totalQcInput.value = formatDisplayNumber(String(total)) || '0';
        }

        if (totalQcText) {
          totalQcText.textContent = formatDisplayNumber(String(total)) || '0';
        }
      }

      numberInputs.forEach(function(input) {
        input.addEventListener('input', function() {
          input.value = input.value.replace(/[^\d.,]/g, '');
          calculateTotalQc();
        });

        input.addEventListener('focus', function() {
          input.value = formatEditableNumber(input.value);
        });

        input.addEventListener('blur', function() {
          input.value = formatDisplayNumber(input.value);
          calculateTotalQc();
        });

        input.value = formatDisplayNumber(input.value);
      });

      calculateTotalQc();

      if (phanBoSelect) {
        phanBoSelect.addEventListener('change', updatePhanBoInfo);
        updatePhanBoInfo();
      }

      if (form) {
        form.addEventListener('submit', function() {
          numberInputs.forEach(function(input) {
            input.value = normalizeNumber(input.value);
          });
          calculateTotalQc();
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
      <h5 class="mb-0">Cập nhật QC</h5>
      <a href="{{ route('qc.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('qc.update', $qc) }}" method="POST" id="qc-form">
        @csrf
        @method('PUT')

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="phan_bo_may_id">Nguồn QC <span class="text-danger">*</span></label>
            <select class="form-select @error('phan_bo_may_id') is-invalid @enderror" id="phan_bo_may_id"
              name="phan_bo_may_id">
              <option value="">-- Chọn nguồn QC --</option>
              @foreach ($phanBoMays as $phanBoMay)
                @php
                  $isOrderSource = (bool) $phanBoMay->source_has_order;
                  $sourceLabelParts = array_filter(
                      [
                          $isOrderSource ? $phanBoMay->source_order_number : null,
                          $isOrderSource ? $phanBoMay->source_customer_number : null,
                          $phanBoMay->source_product_code,
                          $phanBoMay->source_color,
                          $phanBoMay->source_size,
                          $phanBoMay->source_unit_name,
                          $isOrderSource && $phanBoMay->source_order_quantity !== null
                              ? 'SL đặt: ' . $formatPhanBoNumber($phanBoMay->source_order_quantity)
                              : null,
                          'Đã giao may: ' . $formatPhanBoNumber($phanBoMay->source_total_delivered),
                      ],
                      fn($value) => $value !== null && $value !== '',
                  );

                  $sourceLabel = implode(' - ', $sourceLabelParts);
                @endphp
                <option value="{{ $phanBoMay->id }}" data-has-order="{{ $isOrderSource ? '1' : '0' }}"
                  data-order-number="{{ $phanBoMay->source_order_number ?? '' }}"
                  data-customer-number="{{ $phanBoMay->source_customer_number ?? '' }}"
                  data-product-code="{{ $phanBoMay->source_product_code ?? '-' }}"
                  data-product-name="{{ $phanBoMay->source_product_name ?? '-' }}"
                  data-color="{{ $phanBoMay->source_color ?? '-' }}" data-size="{{ $phanBoMay->source_size ?? '-' }}"
                  data-order-quantity="{{ $phanBoMay->source_order_quantity ?? '' }}"
                  data-unit-name="{{ $phanBoMay->source_unit_name ?? '-' }}"
                  data-cut="{{ $phanBoMay->source_total_cut }}"
                  data-delivered="{{ $phanBoMay->source_total_delivered }}"
                  data-qc-done="{{ $phanBoMay->source_total_qc }}"
                  data-remaining="{{ $phanBoMay->source_total_remaining }}" @selected(old('phan_bo_may_id', $selectedPhanBoMayId) == $phanBoMay->id)>
                  {{ $sourceLabel !== '' ? $sourceLabel : 'Nguồn QC #' . $phanBoMay->id }}
                </option>
              @endforeach
            </select>
            @error('phan_bo_may_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="border rounded p-3 bg-light">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="text-muted small">Mã đơn</div>
                  <div class="fw-semibold" id="phanbo-order-number-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Mã KH</div>
                  <div class="fw-semibold" id="phanbo-customer-number-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Mã hàng</div>
                  <div class="fw-semibold" id="phanbo-product-code-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Tên hàng</div>
                  <div class="fw-semibold" id="phanbo-product-name-text">-</div>
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <div class="text-muted small">Màu</div>
                  <div class="fw-semibold" id="phanbo-color-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Size</div>
                  <div class="fw-semibold" id="phanbo-size-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">SL đặt</div>
                  <div class="fw-semibold" id="phanbo-order-quantity-text">-</div>
                </div>
                <div class="col-md-3">
                  <div class="text-muted small">Đơn vị may</div>
                  <div class="fw-semibold" id="phanbo-unit-text">-</div>
                </div>
              </div>
              <div class="row g-3 text-center">
                <div class="col-12 col-md-3">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">SL cắt</div>
                    <div class="fw-semibold" id="phanbo-cut-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-3">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">Đã giao may</div>
                    <div class="fw-semibold" id="phanbo-delivered-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-3">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">SL chưa QC</div>
                    <div class="fw-semibold" id="phanbo-remaining-text">-</div>
                  </div>
                </div>
                <div class="col-12 col-md-3">
                  <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small mb-1">SL đã QC</div>
                    <div class="fw-semibold" id="phanbo-qc-done-text">-</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="ngay_qc">Ngày QC <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('ngay_qc') is-invalid @enderror" id="ngay_qc"
              name="ngay_qc" value="{{ old('ngay_qc', optional($qc->ngay_qc)->format('Y-m-d')) }}" required>
            @error('ngay_qc')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-2">
            <input type="hidden" id="so_luong_qc" name="so_luong_qc"
              value="{{ old('so_luong_qc', $qc->so_luong_qc) }}">
            <div class="border rounded p-3 h-100 bg-light">
              <div class="text-muted small mb-1">Tổng QC</div>
              <div class="fw-semibold"><span
                  id="qc-total-text">{{ formatPhanBoNumber(old('so_luong_qc', $qc->so_luong_qc)) }}</span> sản phẩm
              </div>
              <div class="text-muted small">tự động tính</div>
            </div>
            @error('so_luong_qc')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="so_luong_dat">Số lượng đạt <span class="text-danger">*</span></label>
            <input type="text" inputmode="decimal" autocomplete="off"
              class="form-control js-number-format @error('so_luong_dat') is-invalid @enderror" id="so_luong_dat"
              name="so_luong_dat"
              value="{{ old('so_luong_dat') !== null && old('so_luong_dat') !== '' ? formatPhanBoNumber(old('so_luong_dat')) : formatPhanBoNumber($qc->so_luong_dat) }}"
              required>
            @error('so_luong_dat')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="so_luong_loi">Số lượng lỗi <span class="text-danger">*</span></label>
            <input type="text" inputmode="decimal" autocomplete="off"
              class="form-control js-number-format @error('so_luong_loi') is-invalid @enderror" id="so_luong_loi"
              name="so_luong_loi"
              value="{{ old('so_luong_loi') !== null && old('so_luong_loi') !== '' ? formatPhanBoNumber(old('so_luong_loi')) : formatPhanBoNumber($qc->so_luong_loi) }}"
              required>
            @error('so_luong_loi')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="so_luong_hong">Số lượng hỏng <span class="text-danger">*</span></label>
            <input type="text" inputmode="decimal" autocomplete="off"
              class="form-control js-number-format @error('so_luong_hong') is-invalid @enderror" id="so_luong_hong"
              name="so_luong_hong"
              value="{{ old('so_luong_hong') !== null && old('so_luong_hong') !== '' ? formatPhanBoNumber(old('so_luong_hong')) : formatPhanBoNumber($qc->so_luong_hong) }}"
              required>
            @error('so_luong_hong')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="4">{{ old('ghi_chu', $qc->ghi_chu) }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Cập nhật
              </button>
              <a href="{{ route('qc.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
