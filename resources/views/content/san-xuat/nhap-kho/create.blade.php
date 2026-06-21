@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm nhập kho')

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

  $selectedQcId = old('qc_id', $selectedQcId);
  $sourceOptions = $qcs
      ->map(function ($qc) use ($formatPhanBoNumber) {
          $isOrderSource = (bool) $qc->source_has_order;
          $product = trim(($qc->source_product_code ?? '-') . '/' . ($qc->source_product_name ?? '-'));
          $label = implode(
              ' - ',
              array_filter([
                  $isOrderSource ? $qc->source_order_number : 'Không đơn',
                  $isOrderSource ? $qc->source_customer_number : null,
                  $product,
                  $qc->source_color,
                  $qc->source_size,
                  $qc->source_unit_name,
                  'QC đạt: ' . $formatPhanBoNumber($qc->source_total_qc),
                  'Đã nhập kho: ' . $formatPhanBoNumber($qc->source_total_imported),
                  'Còn lại: ' . $formatPhanBoNumber($qc->source_total_remaining),
              ], fn($value) => $value !== null && $value !== '')
          );

          return [
              'id' => $qc->id,
              'label' => $label !== '' ? $label : 'Nguồn QC #' . $qc->id,
              'search' => mb_strtolower(implode(' ', array_filter([
                  $qc->source_order_number,
                  $qc->source_customer_number,
                  $qc->source_product_code,
                  $qc->source_product_name,
                  $qc->source_color,
                  $qc->source_size,
                  $qc->source_unit_name,
              ], fn($value) => $value !== null && $value !== ''))),
              'has_order' => $isOrderSource,
              'order_number' => $qc->source_order_number,
              'customer_number' => $qc->source_customer_number,
              'product_code' => $qc->source_product_code ?: '-',
              'product_name' => $qc->source_product_name ?: '-',
              'color' => $qc->source_color ?: '-',
              'size' => $qc->source_size ?: '-',
              'unit_name' => $qc->source_unit_name ?: '-',
              'order_quantity' => $qc->source_order_quantity,
              'qc_passed' => $qc->source_total_qc,
              'imported' => $qc->source_total_imported,
              'remaining' => $qc->source_total_remaining,
          ];
      })
      ->values();
  $selectedSource = $sourceOptions->firstWhere('id', (int) $selectedQcId);
@endphp

@section('page-style')
  @parent
  <style>
    .nhap-kho-combobox {
      position: relative;
    }

    .nhap-kho-combobox-menu {
      position: absolute;
      top: calc(100% + .25rem);
      right: 0;
      left: 0;
      z-index: 1080;
      max-height: 280px;
      overflow-y: auto;
      background: var(--bs-body-bg);
      border: 1px solid var(--bs-border-color);
      border-radius: .375rem;
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12);
    }

    .nhap-kho-combobox-item {
      width: 100%;
      padding: .625rem .75rem;
      border: 0;
      background: transparent;
      text-align: left;
      white-space: normal;
    }

    .nhap-kho-combobox-item:hover,
    .nhap-kho-combobox-item:focus {
      background: var(--bs-primary-bg-subtle);
    }
  </style>
@endsection

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const qcSources = @json($sourceOptions);
      const form = document.getElementById('nhap-kho-form');
      const numberInputs = Array.from(document.querySelectorAll('.js-number-format'));
      const qcSearch = document.getElementById('qc_source_search');
      const qcHidden = document.getElementById('qc_id');
      const qcMenu = document.getElementById('qc_source_menu');
      const qcClientError = document.getElementById('qc_source_client_error');
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

      function renderMenu(rows) {
        qcMenu.innerHTML = '';

        rows.slice(0, 80).forEach(function(row) {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'nhap-kho-combobox-item';
          button.textContent = row.label;
          button.addEventListener('mousedown', function(event) {
            event.preventDefault();
            selectQcSource(row);
          });
          qcMenu.appendChild(button);
        });

        qcMenu.classList.toggle('d-none', rows.length === 0);
      }

      function refreshMenu() {
        const keyword = String(qcSearch?.value || '').trim().toLowerCase();
        renderMenu(qcSources.filter(function(row) {
          const haystack = String(row.search || row.label || '').toLowerCase();
          return !keyword || haystack.includes(keyword);
        }));
      }

      function selectQcSource(source) {
        qcHidden.value = source.id;
        qcSearch.value = source.label;
        qcSearch.classList.remove('is-invalid');
        qcClientError?.classList.add('d-none');
        qcMenu.classList.add('d-none');
        updateQcInfo(source);
      }

      function updateQcInfo(source = null) {
        if (!source && qcHidden?.value) {
          source = qcSources.find(row => String(row.id) === String(qcHidden.value));
        }

        if (!source) {
          return;
        }

        const hasOrder = Boolean(source.has_order);
        const orderNumber = source.order_number || '-';
        const customerNumber = source.customer_number || '-';
        const productCode = source.product_code || '-';
        const productName = source.product_name || '-';
        const color = source.color || '-';
        const size = source.size || '-';
        const unitName = source.unit_name || '-';
        const orderQuantity = source.order_quantity || '';
        const qcPassed = source.qc_passed || '';
        const imported = source.imported || '';
        const remaining = source.remaining || '';

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

      if (qcSearch && qcHidden && qcMenu) {
        qcSearch.addEventListener('focus', refreshMenu);
        qcSearch.addEventListener('click', refreshMenu);
        qcSearch.addEventListener('input', function() {
          qcHidden.value = '';
          qcClientError?.classList.add('d-none');
          refreshMenu();
        });
        qcSearch.addEventListener('blur', function() {
          setTimeout(function() {
            const selected = qcSources.find(row => String(row.id) === String(qcHidden.value));
            if (!selected || selected.label !== qcSearch.value) {
              qcHidden.value = '';
            }
            qcMenu.classList.add('d-none');
          }, 150);
        });
        updateQcInfo();
      }

      if (form) {
        form.addEventListener('submit', function(event) {
          if (qcSearch && qcHidden && !qcHidden.value) {
            event.preventDefault();
            qcSearch.classList.add('is-invalid');
            qcClientError?.classList.remove('d-none');
            qcSearch.focus();
            return;
          }

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

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Thêm nhập kho</h5>
      <a href="{{ route('nhap-kho.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('nhap-kho.store') }}" method="POST" id="nhap-kho-form">
        @csrf

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="qc_id">Nguồn QC <span class="text-danger">*</span></label>
            <div class="nhap-kho-combobox">
              <input type="text" class="form-control @error('qc_id') is-invalid @enderror" id="qc_source_search"
                autocomplete="off" placeholder="Gõ để tìm mã đơn / mã hàng / màu / size"
                value="{{ $selectedSource['label'] ?? '' }}">
              <input type="hidden" id="qc_id" name="qc_id" value="{{ $selectedQcId }}">
              <div class="nhap-kho-combobox-menu d-none" id="qc_source_menu"></div>
            </div>
            @error('qc_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <div class="invalid-feedback d-none" id="qc_source_client_error">Vui lòng chọn Nguồn QC trong danh sách.</div>
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
                  name="ngay_nhap" value="{{ old('ngay_nhap', now()->format('Y-m-d')) }}" required>
                @error('ngay_nhap')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label" for="so_luong_nhap">SL nhập kho <span class="text-danger">*</span></label>
                <input type="text" inputmode="decimal" autocomplete="off"
                  class="form-control js-number-format @error('so_luong_nhap') is-invalid @enderror"
                  id="so_luong_nhap" name="so_luong_nhap"
                  value="{{ old('so_luong_nhap') !== null && old('so_luong_nhap') !== '' ? formatPhanBoNumber(old('so_luong_nhap')) : '' }}"
                  required>
                @error('so_luong_nhap')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="4">{{ old('ghi_chu') }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Lưu
              </button>
              <a href="{{ route('nhap-kho.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
