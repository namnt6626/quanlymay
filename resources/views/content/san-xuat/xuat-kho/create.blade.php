@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm xuất kho')

@section('page-style')
  <style>
    .xuat-source-dropdown {
      left: 0;
      max-height: 320px;
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
    }

    .xuat-source-option:hover,
    .xuat-source-option.active {
      background-color: var(--bs-gray-100);
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

      const sources = @json($sourceOptions);
      let selectedRows = @json($selectedItems);

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
        const keyword = sourceInput.value.trim().toLowerCase();
        const ids = selectedIds();

        return sources
          .filter((source) => !ids.includes(Number(source.id)))
          .filter((source) => keyword === '' || source.search_text.includes(keyword))
          .slice(0, 30);
      }

      function renderDropdown() {
        const options = filteredSources();
        sourceDropdown.innerHTML = '';

        if (!options.length) {
          sourceDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Không có nguồn hàng phù hợp.</div>';
          sourceDropdown.classList.remove('d-none');
          sourceDropdown.classList.add('show');
          return;
        }

        options.forEach((source) => {
          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'dropdown-item xuat-source-option py-2';
          item.innerHTML = `
            <div class="fw-semibold">${source.label}</div>
            <div class="text-muted small">Nhập đạt: ${formatDisplayNumber(source.imported)} | Đã xuất: ${formatDisplayNumber(source.exported)} | Còn lại: ${formatDisplayNumber(source.remaining)}</div>
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

      function addSource(source) {
        syncSelectedQuantities();

        selectedRows.push({
          ...source,
          quantity: '',
        });

        if (kenhBanInput && !kenhBanInput.value && source.kenh_ban) {
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
            <input type="text" class="form-control @error('kenh_ban') is-invalid @enderror" id="kenh_ban"
              name="kenh_ban" value="{{ old('kenh_ban') }}" required>
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
        <div class="position-relative mb-3">
          <label class="form-label" for="xuat-source-search">Nguồn xuất <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="xuat-source-search"
            placeholder="Gõ để tìm mã đơn / mã hàng / màu / size cần xuất" autocomplete="off">
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
