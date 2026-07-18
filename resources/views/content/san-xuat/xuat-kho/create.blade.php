@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm xuất kho')

@section('page-style')
  <style>
    .xuat-source-dropdown {
      left: 0;
      max-height: 420px;
      overflow-y: auto;
      right: 0;
      top: calc(100% + 4px);
      z-index: 30;
    }

    .xuat-source-dropdown.show {
      display: block;
    }

    .xuat-source-option {
      cursor: pointer;
      white-space: normal;
    }

    .xuat-source-option:hover,
    .xuat-source-option.active {
      background-color: var(--bs-gray-100);
    }

    .xuat-source-group-label {
      background-color: var(--bs-gray-100);
      color: var(--bs-secondary-color);
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.5rem 0.875rem;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    .xuat-source-option-main {
      min-width: 0;
    }

    .xuat-source-option-name {
      overflow-wrap: anywhere;
    }

    .xuat-source-option-meta {
      align-items: center;
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem 0.75rem;
      margin-top: 0.35rem;
    }

    .xuat-source-result-count {
      color: var(--bs-secondary-color);
      font-size: 0.8125rem;
      min-height: 1.25rem;
    }

    .xuat-source-search-icon {
      color: var(--bs-secondary-color);
    }

    .xuat-lines-table {
      min-width: 1180px;
    }

    .xuat-lines-table thead th {
      background-color: var(--bs-gray-100);
      color: var(--bs-heading-color);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0;
      text-transform: uppercase;
      white-space: nowrap;
    }

    .xuat-lines-table .col-product {
      min-width: 180px;
    }

    .xuat-lines-table .col-number {
      min-width: 105px;
      white-space: nowrap;
    }

    .xuat-qty-input {
      min-width: 110px;
      text-align: right;
      width: 100%;
    }

    @media (max-width: 575.98px) {
      .xuat-lines-responsive {
        overflow-x: visible;
      }

      .xuat-lines-table {
        min-width: 0;
      }

      .xuat-lines-table thead {
        display: none;
      }

      .xuat-lines-table,
      .xuat-lines-table tbody,
      .xuat-lines-table tr,
      .xuat-lines-table td {
        width: 100%;
      }

      .xuat-lines-table tbody tr[data-source-row="1"] {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem 0.65rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        margin-bottom: 0.875rem;
        padding: 0.75rem;
        background-color: var(--bs-card-bg, #fff);
      }

      .xuat-lines-table tbody tr[data-source-row="1"] td {
        display: block;
        border: 0;
        padding: 0.5rem;
        border-radius: 0.375rem;
        background-color: #fafbfc;
        color: var(--bs-body-color);
        white-space: normal;
        text-align: left !important;
      }

      .xuat-lines-table tbody tr[data-source-row="1"] td::before {
        display: block;
        content: attr(data-label);
        color: var(--bs-secondary-color);
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.15rem;
        opacity: 0.85;
      }

      .xuat-lines-table .xuat-mobile-product {
        min-width: 0;
      }

      .xuat-lines-table .xuat-mobile-product strong,
      .xuat-lines-table .xuat-mobile-product .small {
        display: block;
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
      }

      .xuat-lines-table .xuat-mobile-product .small {
        margin-top: 0.15rem;
        line-height: 1.25;
      }

      .xuat-lines-table .xuat-mobile-qty {
        grid-column: 1 / -1;
        display: flex !important;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 0 !important;
        background-color: transparent !important;
      }

      .xuat-lines-table .xuat-mobile-qty::before {
        flex: 0 0 70px;
        margin-bottom: 0 !important;
      }

      .xuat-lines-table .xuat-mobile-remove {
        grid-column: 1 / -1;
        padding: 0 !important;
        background-color: transparent !important;
      }

      .xuat-lines-table .xuat-mobile-remove::before {
        display: none !important;
      }

      .xuat-lines-table .xuat-mobile-remove .btn {
        width: 100%;
      }

      .xuat-qty-input {
        min-width: 0;
        width: 100%;
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
      const sourceInput = document.getElementById('xuat-source-search');
      const sourceDropdown = document.getElementById('xuat-source-dropdown');
      const linesBody = document.getElementById('xuat-lines-body');
      const emptyRow = document.getElementById('xuat-empty-row');
      const kenhBanInput = document.getElementById('kenh_ban');
      const orderFilter = document.getElementById('xuat-source-order-filter');
      const productFilter = document.getElementById('xuat-source-product-filter');
      const colorFilter = document.getElementById('xuat-source-color-filter');
      const sizeFilter = document.getElementById('xuat-source-size-filter');
      const resetFiltersButton = document.getElementById('xuat-source-reset-filters');
      const resultCount = document.getElementById('xuat-source-result-count');

      const sources = @json($sourceOptions);
      let selectedRows = @json($selectedItems);
      let visibleSources = [];
      let activeSourceIndex = -1;

      function normalizeNumber(value) {
        let text = String(value || '').trim();

        if (!text) return '';

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
          text = commaCount === 1 && parts[parts.length - 1].length !== 3 ? text.replace(',', '.') : text.split(',')
            .join('');
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

        if (!normalized) return '';

        const number = Number(normalized);

        if (Number.isNaN(number)) return '';

        return new Intl.NumberFormat('de-DE', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 4,
        }).format(number);
      }

      function formatEditableNumber(value) {
        return normalizeNumber(value).replace('.', ',');
      }

      function selectedIds() {
        return selectedRows.map((row) => Number(row.id));
      }

      function normalizeSearchText(value) {
        return String(value || '')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase()
          .trim();
      }

      function uniqueSortedValues(rows, valueGetter) {
        return Array.from(new Set(rows.map(valueGetter).filter(Boolean)))
          .sort((a, b) => String(a).localeCompare(String(b), 'vi'));
      }

      function setSelectOptions(select, rows, valueGetter, placeholder, formatter = (value) => value) {
        const currentValue = select.value;
        const values = uniqueSortedValues(rows, valueGetter);

        select.innerHTML = `<option value="">${placeholder}</option>`;
        values.forEach((value) => {
          const option = document.createElement('option');
          option.value = value;
          option.textContent = formatter(value);
          select.appendChild(option);
        });

        select.value = values.includes(currentValue) ? currentValue : '';
      }

      function matchesOrderFilter(source) {
        if (!orderFilter.value) return true;
        if (orderFilter.value === '__no_order__') return !source.has_order;

        return source.has_order && String(source.order_number || '') === orderFilter.value;
      }

      function populateSourceFilters() {
        const orderValue = orderFilter.value;
        const orderRows = sources.filter((source) => source.has_order);
        const orderNumbers = uniqueSortedValues(orderRows, (source) => source.order_number);
        const hasNoOrder = sources.some((source) => !source.has_order);

        orderFilter.innerHTML = '<option value="">Tất cả mã đơn</option>';
        if (hasNoOrder) {
          orderFilter.appendChild(new Option('Không đơn hàng', '__no_order__'));
        }
        orderNumbers.forEach((value) => orderFilter.appendChild(new Option(value, value)));
        orderFilter.value = orderValue === '__no_order__' || orderNumbers.includes(orderValue) ? orderValue : '';

        const productRows = sources.filter(matchesOrderFilter);
        setSelectOptions(
          productFilter,
          productRows,
          (source) => String(source.product_code || ''),
          'Tất cả mã hàng',
          (code) => {
            const source = productRows.find((item) => String(item.product_code || '') === code);
            return source?.product_name ? `${code} - ${source.product_name}` : code;
          }
        );

        const colorRows = productRows.filter((source) => !productFilter.value || String(source.product_code || '') === productFilter.value);
        setSelectOptions(colorFilter, colorRows, (source) => String(source.color || ''), 'Tất cả màu');

        const sizeRows = colorRows.filter((source) => !colorFilter.value || String(source.color || '') === colorFilter.value);
        setSelectOptions(sizeFilter, sizeRows, (source) => String(source.size || ''), 'Tất cả size');
      }

      function syncSelectedQuantities() {
        linesBody.querySelectorAll('tr[data-source-row="1"]').forEach((row) => {
          const sourceId = Number(row.dataset.sourceId);
          const input = row.querySelector('.js-xuat-qty');
          const selectedRow = selectedRows.find((item) => Number(item.id) === sourceId);

          if (selectedRow && input) {
            selectedRow.quantity = input.value;
          }

        });
      }

      function filteredSources() {
        const keyword = normalizeSearchText(sourceInput.value);
        const ids = selectedIds();

        return sources
          .filter((source) => !ids.includes(Number(source.id)))
          .filter(matchesOrderFilter)
          .filter((source) => !productFilter.value || String(source.product_code || '') === productFilter.value)
          .filter((source) => !colorFilter.value || String(source.color || '') === colorFilter.value)
          .filter((source) => !sizeFilter.value || String(source.size || '') === sizeFilter.value)
          .filter((source) => keyword === '' || normalizeSearchText(source.search_text).includes(keyword))
          .sort((a, b) => {
            return String(a.order_number || 'Không đơn').localeCompare(String(b.order_number || 'Không đơn'), 'vi') ||
              String(a.product_code || '').localeCompare(String(b.product_code || ''), 'vi') ||
              String(a.color || '').localeCompare(String(b.color || ''), 'vi') ||
              String(a.size || '').localeCompare(String(b.size || ''), 'vi');
          });
      }

      function renderDropdown() {
        const matchingSources = filteredSources();
        visibleSources = matchingSources.slice(0, 100);
        activeSourceIndex = -1;
        sourceDropdown.innerHTML = '';
        resultCount.textContent = matchingSources.length > visibleSources.length
          ? `Hiển thị ${visibleSources.length} / ${matchingSources.length} nguồn phù hợp`
          : `${matchingSources.length} nguồn phù hợp`;

        if (!visibleSources.length) {
          sourceDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Không có nguồn hàng phù hợp.</div>';
          sourceDropdown.classList.remove('d-none');
          sourceDropdown.classList.add('show');
          return;
        }

        let previousGroup = null;

        visibleSources.forEach((source, index) => {
          const groupLabel = source.has_order ? `Đơn ${source.order_number}` : 'Không theo đơn hàng';

          if (groupLabel !== previousGroup) {
            const group = document.createElement('div');
            group.className = 'xuat-source-group-label';
            group.textContent = groupLabel;
            sourceDropdown.appendChild(group);
            previousGroup = groupLabel;
          }

          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'dropdown-item xuat-source-option py-2';
          item.dataset.sourceIndex = index;
          item.innerHTML = `
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div class="xuat-source-option-main">
                <div class="fw-semibold xuat-source-option-name">${source.product_code || '-'} - ${source.product_name || '-'}</div>
                <div class="xuat-source-option-meta small">
                  <span class="badge bg-label-secondary">${source.color || '-'}</span>
                  <span class="badge bg-label-info">Size ${source.size || '-'}</span>
                  ${source.customer_number ? `<span class="text-muted">KH: ${source.customer_number}</span>` : ''}
                </div>
                <div class="text-muted small mt-1">
                  Nhập đạt: ${formatDisplayNumber(source.imported)} · Đã xuất: ${formatDisplayNumber(source.exported)}
                </div>
              </div>
              <span class="badge bg-label-success flex-shrink-0">Còn ${formatDisplayNumber(source.remaining)}</span>
            </div>
          `;
          item.addEventListener('click', function() {
            addSource(source);
          });
          sourceDropdown.appendChild(item);
        });

        sourceDropdown.classList.remove('d-none');
        sourceDropdown.classList.add('show');
      }

      function hideDropdown() {
        window.setTimeout(function() {
          sourceDropdown.classList.add('d-none');
          sourceDropdown.classList.remove('show');
        }, 160);
      }

      function updateActiveSource(index) {
        if (!visibleSources.length) return;

        activeSourceIndex = Math.max(0, Math.min(index, visibleSources.length - 1));
        sourceDropdown.querySelectorAll('.xuat-source-option').forEach((item) => {
          item.classList.toggle('active', Number(item.dataset.sourceIndex) === activeSourceIndex);
        });

        sourceDropdown.querySelector(`[data-source-index="${activeSourceIndex}"]`)
          ?.scrollIntoView({ block: 'nearest' });
      }

      function addSource(source) {
        syncSelectedQuantities();

        selectedRows.push({
          ...source,
          quantity: '',
        });

        if (kenhBanInput && !kenhBanInput.value && ['Tiktok', 'Shopee', 'Bán sỉ'].includes(source.kenh_ban)) {
          kenhBanInput.value = source.kenh_ban;
        }

        sourceInput.value = '';
        sourceDropdown.classList.add('d-none');
        sourceDropdown.classList.remove('show');
        renderRows();
      }

      function removeSource(id) {
        syncSelectedQuantities();
        selectedRows = selectedRows.filter((row) => Number(row.id) !== Number(id));
        renderRows();
      }

      function renderRows() {
        linesBody.querySelectorAll('tr[data-source-row="1"]').forEach((row) => row.remove());
        emptyRow.classList.toggle('d-none', selectedRows.length > 0);

        selectedRows.forEach((row, index) => {
          const tr = document.createElement('tr');
          tr.dataset.sourceRow = '1';
          tr.dataset.sourceId = row.id;
          tr.innerHTML = `
            <td data-label="STT">${index + 1}</td>
            <td data-label="Mã đơn">${row.order_number || '-'}</td>
            <td data-label="Mã KH">${row.customer_number || '-'}</td>
            <td class="col-product xuat-mobile-product" data-label="Mã hàng">
              <strong>${row.product_code || '-'}</strong>
              <div class="text-muted small">${row.product_name || '-'}</div>
            </td>
            <td data-label="Màu">${row.color || '-'}</td>
            <td data-label="Size">${row.size || '-'}</td>
            <td class="text-end col-number" data-label="SL đặt">${row.order_quantity !== null && row.order_quantity !== '' ? formatDisplayNumber(row.order_quantity) : '-'}</td>
            <td class="text-end col-number" data-label="Nhập đạt">${formatDisplayNumber(row.imported)}</td>
            <td class="text-end col-number" data-label="Đã xuất">${formatDisplayNumber(row.exported)}</td>
            <td class="text-end col-number fw-semibold" data-label="Còn lại">${formatDisplayNumber(row.remaining)}</td>
            <td class="col-number xuat-mobile-qty" data-label="SL xuất">
              <input type="hidden" name="items[${index}][nhap_kho_id]" value="${row.id}">
              <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control xuat-qty-input js-xuat-qty"
                name="items[${index}][so_luong_xuat]"
                value="${row.quantity ? formatDisplayNumber(row.quantity) : ''}">
            </td>
            <td class="text-center xuat-mobile-remove">
              <button type="button" class="btn btn-sm btn-outline-danger" data-remove-source="${row.id}" title="Xóa">
                <i class="icon-base bx bx-trash me-1"></i> Xóa
              </button>
            </td>
          `;
          linesBody.appendChild(tr);
        });
      }

      linesBody.addEventListener('click', function(event) {
        const button = event.target.closest('[data-remove-source]');
        if (!button) return;
        removeSource(button.dataset.removeSource);
      });

      linesBody.addEventListener('input', function(event) {
        if (!event.target.classList.contains('js-xuat-qty')) return;

        event.target.value = event.target.value.replace(/[^\d.,]/g, '');

        const row = event.target.closest('tr[data-source-row="1"]');
        const selectedRow = selectedRows.find((item) => Number(item.id) === Number(row?.dataset.sourceId));

        if (selectedRow) {
          selectedRow.quantity = event.target.value;
        }
      });

      linesBody.addEventListener('focusin', function(event) {
        if (!event.target.classList.contains('js-xuat-qty')) return;
        event.target.value = formatEditableNumber(event.target.value);
      });

      linesBody.addEventListener('focusout', function(event) {
        if (!event.target.classList.contains('js-xuat-qty')) return;
        event.target.value = formatDisplayNumber(event.target.value);

        const row = event.target.closest('tr[data-source-row="1"]');
        const selectedRow = selectedRows.find((item) => Number(item.id) === Number(row?.dataset.sourceId));

        if (selectedRow) {
          selectedRow.quantity = event.target.value;
        }
      });

      sourceInput.addEventListener('focus', renderDropdown);
      sourceInput.addEventListener('input', renderDropdown);
      sourceInput.addEventListener('blur', hideDropdown);
      sourceInput.addEventListener('keydown', function(event) {
        if (event.key === 'ArrowDown') {
          event.preventDefault();
          updateActiveSource(activeSourceIndex + 1);
        } else if (event.key === 'ArrowUp') {
          event.preventDefault();
          updateActiveSource(activeSourceIndex <= 0 ? visibleSources.length - 1 : activeSourceIndex - 1);
        } else if (event.key === 'Enter' && activeSourceIndex >= 0) {
          event.preventDefault();
          addSource(visibleSources[activeSourceIndex]);
        } else if (event.key === 'Escape') {
          sourceDropdown.classList.add('d-none');
          sourceDropdown.classList.remove('show');
        }
      });

      orderFilter.addEventListener('change', function() {
        productFilter.value = '';
        colorFilter.value = '';
        sizeFilter.value = '';
        populateSourceFilters();
        renderDropdown();
      });

      productFilter.addEventListener('change', function() {
        colorFilter.value = '';
        sizeFilter.value = '';
        populateSourceFilters();
        renderDropdown();
      });

      colorFilter.addEventListener('change', function() {
        sizeFilter.value = '';
        populateSourceFilters();
        renderDropdown();
      });

      sizeFilter.addEventListener('change', renderDropdown);

      resetFiltersButton.addEventListener('click', function() {
        orderFilter.value = '';
        productFilter.value = '';
        colorFilter.value = '';
        sizeFilter.value = '';
        sourceInput.value = '';
        populateSourceFilters();
        renderDropdown();
        sourceInput.focus();
      });

      if (form) {
        form.addEventListener('submit', function() {
          syncSelectedQuantities();

          document.querySelectorAll('.js-xuat-qty').forEach((input) => {
            input.value = normalizeNumber(input.value);
          });

          form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Đang lưu';
          });
        });
      }

      populateSourceFilters();
      renderRows();
    });
  </script>
@endsection

@section('content')
  @include('content.danh-muc._toast')

  @php
    $xuatKhoSubmitToken = old('xuat_kho_submit_token', (string) \Illuminate\Support\Str::uuid());
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

  <form action="{{ route('xuat-kho.store') }}" method="POST" id="xuat-kho-form">
    @csrf
    <input type="hidden" name="xuat_kho_submit_token" value="{{ $xuatKhoSubmitToken }}">

    <div class="card mb-4">
      <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
        <h5 class="mb-0">Thông tin phiếu xuất</h5>
        <a href="{{ route('xuat-kho.index') }}" class="btn btn-outline-secondary">
          <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
        </a>
      </div>

      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Vui lòng kiểm tra lại dữ liệu.</div>
            <ul class="mb-0 ps-3">
              @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="row g-3">
          <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label" for="so_phieu">Số phiếu <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('so_phieu') is-invalid @enderror" id="so_phieu"
              name="so_phieu" value="{{ old('so_phieu') }}" required>
            @error('so_phieu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label" for="ngay_xuat">Ngày xuất <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('ngay_xuat') is-invalid @enderror" id="ngay_xuat"
              name="ngay_xuat" value="{{ old('ngay_xuat', now()->format('Y-m-d')) }}" required>
            @error('ngay_xuat')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label" for="kenh_ban">Kênh bán <span class="text-danger">*</span></label>
            <select class="form-select @error('kenh_ban') is-invalid @enderror" id="kenh_ban" name="kenh_ban" required>
              <option value="">-- Chọn kênh bán --</option>
              <option value="Tiktok" @selected(old('kenh_ban', 'Tiktok') === 'Tiktok')>Tiktok</option>
              <option value="Shopee" @selected(old('kenh_ban') === 'Shopee')>Shopee</option>
              <option value="Bán sỉ" @selected(old('kenh_ban') === 'Bán sỉ')>Bán sỉ</option>
            </select>
            @error('kenh_ban')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <input type="text" class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu"
              name="ghi_chu" value="{{ old('ghi_chu') }}">
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Nguồn hàng xuất</h5>
      </div>

      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-6 col-xl-3">
            <label class="form-label" for="xuat-source-order-filter">Mã đơn</label>
            <select class="form-select" id="xuat-source-order-filter">
              <option value="">Tất cả mã đơn</option>
            </select>
          </div>
          <div class="col-6 col-xl-3">
            <label class="form-label" for="xuat-source-product-filter">Mã hàng</label>
            <select class="form-select" id="xuat-source-product-filter">
              <option value="">Tất cả mã hàng</option>
            </select>
          </div>
          <div class="col-6 col-xl-2">
            <label class="form-label" for="xuat-source-color-filter">Màu</label>
            <select class="form-select" id="xuat-source-color-filter">
              <option value="">Tất cả màu</option>
            </select>
          </div>
          <div class="col-6 col-xl-2">
            <label class="form-label" for="xuat-source-size-filter">Size</label>
            <select class="form-select" id="xuat-source-size-filter">
              <option value="">Tất cả size</option>
            </select>
          </div>
          <div class="col-12 col-xl-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary w-100" id="xuat-source-reset-filters">
              <i class="icon-base bx bx-refresh me-1"></i> Xóa lọc
            </button>
          </div>
        </div>

        <div class="position-relative mb-3">
          <label class="form-label" for="xuat-source-search">Nguồn xuất <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text xuat-source-search-icon"><i class="icon-base bx bx-search"></i></span>
            <input type="text" class="form-control" id="xuat-source-search"
              placeholder="Tìm mã đơn, mã KH, mã hàng, tên hàng, màu hoặc size" autocomplete="off">
          </div>
          <div class="xuat-source-result-count mt-1" id="xuat-source-result-count"></div>
          <div id="xuat-source-dropdown" class="dropdown-menu shadow xuat-source-dropdown d-none"></div>
          @error('items')
            <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>

        <div class="table-responsive xuat-lines-responsive">
          <table class="table table-hover align-middle mb-0 xuat-lines-table">
            <thead>
              <tr>
                <th style="width: 70px;">STT</th>
                <th>Mã đơn</th>
                <th>Mã KH</th>
                <th class="col-product">Mã hàng</th>
                <th>Màu</th>
                <th>Size</th>
                <th class="text-end col-number">SL đặt</th>
                <th class="text-end col-number">Nhập đạt</th>
                <th class="text-end col-number">Đã xuất</th>
                <th class="text-end col-number">Còn lại</th>
                <th class="col-number">SL xuất</th>
                <th class="text-center" style="width: 80px;">Xóa</th>
              </tr>
            </thead>
            <tbody id="xuat-lines-body">
              <tr id="xuat-empty-row">
                <td colspan="12" class="text-center py-4 text-muted">Chưa chọn nguồn hàng xuất.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer">
        <div class="d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-primary">
            <i class="icon-base bx bx-save me-1"></i> Lưu
          </button>
          <a href="{{ route('xuat-kho.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
      </div>
    </div>
  </form>
@endsection
