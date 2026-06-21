@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm QC')

@php
  $sourceOptions = $phanBoMays
      ->map(function ($phanBoMay) {
          $cat = $phanBoMay->cat;
          $donHang = $phanBoMay->donHangChiTiet?->donHang;
          $label = $phanBoMay->don_hang_chi_tiet_id
              ? implode(' - ', array_filter([
                  $donHang?->ma_don,
                  $donHang?->ma_kh,
                  ($cat?->matHang?->ma_hang ?? '-') . '/' . ($cat?->matHang?->ten_hang ?? '-'),
                  $cat?->mau?->ten_mau,
                  $cat?->size?->ten_size,
                  $phanBoMay->donViMay?->ten_don_vi,
                  'SL giao: ' . formatPhanBoNumber($phanBoMay->source_total_delivered ?? 0),
              ]))
              : implode(' - ', array_filter([
                  'Không đơn',
                  ($cat?->matHang?->ma_hang ?? '-') . '/' . ($cat?->matHang?->ten_hang ?? '-'),
                  $cat?->mau?->ten_mau,
                  $cat?->size?->ten_size,
                  $phanBoMay->donViMay?->ten_don_vi,
                  'SL giao: ' . formatPhanBoNumber($phanBoMay->source_total_delivered ?? 0),
              ]));

          return [
              'id' => $phanBoMay->id,
              'label' => $label,
              'search' => mb_strtolower($label),
              'ma_don' => $donHang?->ma_don,
              'ma_kh' => $donHang?->ma_kh,
              'ma_hang' => $cat?->matHang?->ma_hang,
              'ten_hang' => $cat?->matHang?->ten_hang,
              'ten_mau' => $cat?->mau?->ten_mau,
              'ten_size' => $cat?->size?->ten_size,
              'don_vi_may' => $phanBoMay->donViMay?->ten_don_vi,
              'sl_giao' => $phanBoMay->source_total_delivered,
              'sl_chua_qc' => $phanBoMay->source_total_remaining,
          ];
      })
      ->values();

  $productOptions = $manualProducts
      ->map(fn($matHang) => [
          'id' => $matHang->id,
          'label' => ($matHang->ma_hang ?? '-') . ' - ' . ($matHang->ten_hang ?? '-'),
          'search' => mb_strtolower(($matHang->ma_hang ?? '') . ' ' . ($matHang->ten_hang ?? '')),
      ])
      ->values();

  $oldAllocationGroups = collect(old('allocation_groups', []))
      ->filter(fn($group) => is_array($group))
      ->values();
  $oldManualGroups = collect(old('manual_groups', []))
      ->filter(fn($group) => is_array($group))
      ->values();
@endphp

@section('page-style')
  @parent
  <style>
    .qc-combobox {
      position: relative;
    }

    .qc-combobox-menu {
      position: absolute;
      top: calc(100% + .25rem);
      right: 0;
      left: 0;
      z-index: 1080;
      max-height: 260px;
      overflow-y: auto;
      background: var(--bs-body-bg);
      border: 1px solid var(--bs-border-color);
      border-radius: .375rem;
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12);
    }

    .qc-combobox-item {
      width: 100%;
      padding: .625rem .75rem;
      border: 0;
      background: transparent;
      text-align: left;
    }

    .qc-combobox-item:hover,
    .qc-combobox-item:focus {
      background: var(--bs-primary-bg-subtle);
    }

    .qc-source-table {
      min-width: 1200px;
      table-layout: auto;
    }

    .qc-source-table th,
    .qc-source-table td {
      vertical-align: middle;
    }

    .qc-source-name {
      max-width: 220px;
      min-width: 180px;
      white-space: normal;
      word-break: break-word;
    }

    .qc-col-delivered {
      min-width: 80px;
      white-space: nowrap;
    }

    .qc-col-remaining {
      min-width: 90px;
      white-space: nowrap;
    }

    .qc-col-number {
      min-width: 110px;
    }

    .qc-col-diff {
      min-width: 130px;
      white-space: nowrap;
    }

    .qc-number-input {
      min-width: 110px;
      width: 100%;
      padding-right: .75rem;
      text-align: right;
    }
  </style>
@endsection

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const sources = @json($sourceOptions);
      const products = @json($productOptions);
      const manualItems = @json($manualItems);
      const oldAllocationGroups = @json($oldAllocationGroups);
      const oldManualGroups = @json($oldManualGroups);

      const form = document.getElementById('qc-form');
      const sourceBlocks = document.getElementById('source-blocks');
      const manualBlocks = document.getElementById('manual-blocks');
      const addSourceButton = document.getElementById('add-source-block');
      const addProductButton = document.getElementById('add-product-block');
      const sourceSection = document.getElementById('source-section');
      const manualSection = document.getElementById('manual-section');
      const modeInputs = Array.from(document.querySelectorAll('input[name="qc_mode"]'));
      let sourceIndex = 0;
      let productIndex = 0;

      function normalizeNumber(value) {
        let text = String(value || '').trim();
        if (!text) return '';

        text = text.replace(/\s+/g, '');
        const commaCount = (text.match(/,/g) || []).length;
        const dotCount = (text.match(/\./g) || []).length;

        if (commaCount > 0 && dotCount > 0) {
          const decimalSeparator = text.lastIndexOf(',') > text.lastIndexOf('.') ? ',' : '.';
          const thousandSeparator = decimalSeparator === ',' ? '.' : ',';
          text = text.split(thousandSeparator).join('').replace(decimalSeparator, '.');
        } else if (commaCount > 0) {
          text = text.split('.').join('').replace(',', '.');
        } else if (dotCount > 0) {
          const parts = text.split('.');
          if (!(dotCount === 1 && parts[parts.length - 1].length !== 3)) text = text.split('.').join('');
        }

        text = text.replace(/[^\d.\-]/g, '');
        const dotIndex = text.indexOf('.');
        if (dotIndex !== -1) text = text.slice(0, dotIndex + 1) + text.slice(dotIndex + 1).replace(/\./g, '');

        return text;
      }

      function formatDisplayNumber(value) {
        const normalized = normalizeNumber(value);
        if (normalized === '') return '';
        const number = Number(normalized);
        if (Number.isNaN(number)) return '';
        return new Intl.NumberFormat('de-DE', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 4
        }).format(number);
      }

      function numberValue(input) {
        return Number(normalizeNumber(input?.value || '')) || 0;
      }

      function wireNumberInput(input, onInput) {
        input.addEventListener('input', function() {
          input.value = input.value.replace(/[^\d.,]/g, '');
          if (onInput) onInput();
        });
        input.addEventListener('focus', function() {
          input.value = normalizeNumber(input.value).replace('.', ',');
        });
        input.addEventListener('blur', function() {
          input.value = formatDisplayNumber(input.value);
        });
      }

      function renderMenu(menu, rows, onSelect) {
        menu.innerHTML = '';
        rows.slice(0, 80).forEach(function(row) {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'qc-combobox-item';
          button.textContent = row.label;
          button.addEventListener('mousedown', function(event) {
            event.preventDefault();
            onSelect(row);
          });
          menu.appendChild(button);
        });
        menu.classList.toggle('d-none', rows.length === 0);
      }

      function selectedSourceIds(exceptHidden = null) {
        return Array.from(sourceBlocks.querySelectorAll('.js-source-id'))
          .filter(hidden => hidden !== exceptHidden)
          .map(hidden => String(hidden.value || ''))
          .filter(Boolean);
      }

      function refreshSourceMenus() {
        sourceBlocks.querySelectorAll('.js-source-search').forEach(function(input) {
          if (document.activeElement === input) {
            input.qcRefreshMenu?.();
          }
        });
      }

      function bindCombobox(input, hidden, menu, rows, onSelect, filterRows = null) {
        function refresh() {
          const keyword = String(input.value || '').trim().toLowerCase();
          const availableRows = filterRows ? rows.filter(row => filterRows(row, hidden)) : rows;
          renderMenu(menu, availableRows.filter(row => !keyword || String(row.search || row.label).toLowerCase().includes(keyword)),
            function(row) {
              input.value = row.label;
              hidden.value = row.id;
              menu.classList.add('d-none');
              input.classList.remove('is-invalid');
              onSelect(row);
              refreshSourceMenus();
            });
        }

        input.addEventListener('focus', refresh);
        input.addEventListener('click', refresh);
        input.qcRefreshMenu = refresh;
        input.addEventListener('input', function() {
          hidden.value = '';
          refresh();
        });
        input.addEventListener('blur', function() {
          setTimeout(function() {
            const selected = rows.find(row => String(row.id) === String(hidden.value));
            if (!selected || selected.label !== input.value) hidden.value = '';
            menu.classList.add('d-none');
          }, 150);
        });
      }

      function updateTotal(row) {
        const parts = Array.from(row.querySelectorAll('.js-qc-part'));
        const total = parts.reduce((sum, input) => sum + numberValue(input), 0);
        const totalInput = row.querySelector('.js-qc-total');
        if (totalInput) totalInput.value = formatDisplayNumber(total);

        const diffElement = row.querySelector('.js-qc-diff');
        if (diffElement) {
          const remaining = Number(diffElement.dataset.remaining || 0);
          const diff = total - remaining;
          const absDiff = Math.abs(diff);

          if (diff < 0) {
            diffElement.innerHTML = `<span class="badge bg-warning-subtle text-warning">QC thiếu ${formatDisplayNumber(absDiff)}</span>`;
          } else if (diff > 0) {
            diffElement.innerHTML = `<span class="badge bg-info-subtle text-info">QC thừa ${formatDisplayNumber(absDiff)}</span>`;
          } else {
            diffElement.innerHTML = '<span class="badge bg-success-subtle text-success">QC đủ</span>';
          }
        }
      }

      function sourceBlockTemplate(index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'border rounded p-3 source-block';
        wrapper.innerHTML = `
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0">Nguồn QC</h6>
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-source">Xóa</button>
          </div>
          <div class="qc-combobox mb-3">
            <input type="text" class="form-control js-source-search" autocomplete="off" placeholder="Gõ mã đơn, mã hàng, màu, size hoặc đơn vị may">
            <input type="hidden" class="js-source-id" name="allocation_groups[${index}][phan_bo_may_id]">
            <div class="qc-combobox-menu d-none js-source-menu"></div>
          </div>
          <div class="table-responsive d-none js-source-table">
            <table class="table align-middle mb-0 qc-source-table">
              <thead>
                <tr>
                  <th class="qc-source-name">Nguồn QC</th>
                  <th>Mã đơn</th>
                  <th>Mã hàng</th>
                  <th>Màu</th>
                  <th>Size</th>
                  <th>Đơn vị may</th>
                  <th class="text-end qc-col-delivered">SL giao</th>
                  <th class="text-end qc-col-remaining">SL chưa QC</th>
                  <th class="qc-col-number">SL đạt</th>
                  <th class="qc-col-number">SL lỗi</th>
                  <th class="qc-col-number">SL hỏng</th>
                  <th class="qc-col-number">Tổng QC</th>
                  <th class="qc-col-diff">Chênh lệch</th>
                </tr>
              </thead>
              <tbody class="js-source-body"></tbody>
            </table>
          </div>
        `;
        return wrapper;
      }

      function renderSelectedSource(block, source, index, values = {}) {
        const body = block.querySelector('.js-source-body');
        block.querySelector('.js-source-table').classList.remove('d-none');
        body.innerHTML = `
          <tr>
            <td class="small qc-source-name" title="${source.label}">${source.label}</td>
            <td>${source.ma_don || '-'}</td>
            <td><strong>${source.ma_hang || '-'}</strong><div class="text-muted small">${source.ten_hang || '-'}</div></td>
            <td>${source.ten_mau || '-'}</td>
            <td>${source.ten_size || '-'}</td>
            <td>${source.don_vi_may || '-'}</td>
            <td class="text-end qc-col-delivered">${formatDisplayNumber(source.sl_giao)}</td>
            <td class="text-end qc-col-remaining">${formatDisplayNumber(source.sl_chua_qc)}</td>
            <td class="qc-col-number"><input type="text" inputmode="decimal" autocomplete="off" class="form-control qc-number-input js-number-format js-qc-part" name="allocation_groups[${index}][sl_dat]" value="${formatDisplayNumber(values.sl_dat || '')}"></td>
            <td class="qc-col-number"><input type="text" inputmode="decimal" autocomplete="off" class="form-control qc-number-input js-number-format js-qc-part" name="allocation_groups[${index}][sl_loi]" value="${formatDisplayNumber(values.sl_loi || '')}"></td>
            <td class="qc-col-number"><input type="text" inputmode="decimal" autocomplete="off" class="form-control qc-number-input js-number-format js-qc-part" name="allocation_groups[${index}][sl_hong]" value="${formatDisplayNumber(values.sl_hong || '')}"></td>
            <td class="qc-col-number"><input type="text" class="form-control qc-number-input js-qc-total" readonly tabindex="-1"></td>
            <td class="qc-col-diff js-qc-diff" data-remaining="${source.sl_chua_qc || 0}"></td>
          </tr>
        `;
        body.querySelectorAll('.js-number-format').forEach(input => wireNumberInput(input, () => updateTotal(body)));
        updateTotal(body);
      }

      function addSourceBlock(values = {}) {
        const index = sourceIndex++;
        const block = sourceBlockTemplate(index);
        sourceBlocks.appendChild(block);
        bindCombobox(
          block.querySelector('.js-source-search'),
          block.querySelector('.js-source-id'),
          block.querySelector('.js-source-menu'),
          sources,
          source => renderSelectedSource(block, source, index),
          (source, hidden) => !selectedSourceIds(hidden).includes(String(source.id))
        );

        if (values.phan_bo_may_id) {
          const source = sources.find(row => String(row.id) === String(values.phan_bo_may_id));

          block.querySelector('.js-source-id').value = values.phan_bo_may_id;
          block.querySelector('.js-source-search').value = source ? source.label : '';

          if (source) {
            renderSelectedSource(block, source, index, values);
          }
        }

        block.querySelector('.js-remove-source').addEventListener('click', function() {
          if (sourceBlocks.querySelectorAll('.source-block').length > 1) {
            block.remove();
            refreshSourceMenus();
          }
        });
      }

      function productBlockTemplate(index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'border rounded p-3 product-block';
        wrapper.innerHTML = `
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0">Mã hàng</h6>
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-product">Xóa</button>
          </div>
          <div class="qc-combobox mb-3">
            <input type="text" class="form-control js-product-search" autocomplete="off" placeholder="Gõ mã hàng hoặc tên hàng">
            <input type="hidden" class="js-product-id" name="manual_groups[${index}][mat_hang_id]">
            <div class="qc-combobox-menu d-none js-product-menu"></div>
          </div>
          <div class="table-responsive d-none js-product-table">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Màu</th>
                  <th>Size</th>
                  <th>SL đạt</th>
                  <th>SL lỗi</th>
                  <th>SL hỏng</th>
                  <th>Tổng QC</th>
                </tr>
              </thead>
              <tbody class="js-product-body"></tbody>
            </table>
          </div>
        `;
        return wrapper;
      }

      function renderProductRows(block, product, groupIndex, oldItems = []) {
        const rows = manualItems.filter(item => String(item.mat_hang_id) === String(product.id));
        const body = block.querySelector('.js-product-body');
        block.querySelector('.js-product-table').classList.toggle('d-none', rows.length === 0);
        body.innerHTML = '';
        rows.forEach(function(item, itemIndex) {
          const oldItem = oldItems.find(function(row) {
            return String(row.mau_id || '') === String(item.mau_id || '') &&
              String(row.size_id || '') === String(item.size_id || '');
          }) || {};
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>
              ${item.ten_mau || '-'}
              <input type="hidden" name="manual_groups[${groupIndex}][items][${itemIndex}][mau_id]" value="${item.mau_id || ''}">
              <input type="hidden" name="manual_groups[${groupIndex}][items][${itemIndex}][size_id]" value="${item.size_id || ''}">
            </td>
            <td>${item.ten_size || '-'}</td>
            <td><input type="text" inputmode="decimal" autocomplete="off" class="form-control js-number-format js-qc-part" name="manual_groups[${groupIndex}][items][${itemIndex}][sl_dat]" value="${formatDisplayNumber(oldItem.sl_dat || '')}"></td>
            <td><input type="text" inputmode="decimal" autocomplete="off" class="form-control js-number-format js-qc-part" name="manual_groups[${groupIndex}][items][${itemIndex}][sl_loi]" value="${formatDisplayNumber(oldItem.sl_loi || '')}"></td>
            <td><input type="text" inputmode="decimal" autocomplete="off" class="form-control js-number-format js-qc-part" name="manual_groups[${groupIndex}][items][${itemIndex}][sl_hong]" value="${formatDisplayNumber(oldItem.sl_hong || '')}"></td>
            <td><input type="text" class="form-control js-qc-total" readonly tabindex="-1"></td>
          `;
          body.appendChild(tr);
          tr.querySelectorAll('.js-number-format').forEach(input => wireNumberInput(input, () => updateTotal(tr)));
          updateTotal(tr);
        });
      }

      function addProductBlock(values = {}) {
        const index = productIndex++;
        const block = productBlockTemplate(index);
        manualBlocks.appendChild(block);
        bindCombobox(
          block.querySelector('.js-product-search'),
          block.querySelector('.js-product-id'),
          block.querySelector('.js-product-menu'),
          products,
          product => renderProductRows(block, product, index)
        );

        if (values.mat_hang_id) {
          const product = products.find(row => String(row.id) === String(values.mat_hang_id));

          block.querySelector('.js-product-id').value = values.mat_hang_id;
          block.querySelector('.js-product-search').value = product ? product.label : '';

          if (product) {
            renderProductRows(block, product, index, values.items || []);
          }
        }

        block.querySelector('.js-remove-product').addEventListener('click', function() {
          if (manualBlocks.querySelectorAll('.product-block').length > 1) block.remove();
        });
      }

      function currentMode() {
        return document.querySelector('input[name="qc_mode"]:checked')?.value || 'from_allocation';
      }

      function toggleMode() {
        const isManual = currentMode() === 'manual';
        sourceSection.classList.toggle('d-none', isManual);
        manualSection.classList.toggle('d-none', !isManual);
      }

      addSourceButton.addEventListener('click', addSourceBlock);
      addProductButton.addEventListener('click', addProductBlock);
      modeInputs.forEach(input => input.addEventListener('change', toggleMode));

      if (form) {
        form.addEventListener('submit', function(event) {
          form.querySelectorAll('.js-number-format').forEach(input => {
            input.value = normalizeNumber(input.value) || '0';
          });
        });
      }

      if (oldAllocationGroups.length > 0) {
        oldAllocationGroups.forEach(group => addSourceBlock(group || {}));
      } else {
        addSourceBlock();
      }

      if (oldManualGroups.length > 0) {
        oldManualGroups.forEach(group => addProductBlock(group || {}));
      } else {
        addProductBlock();
      }

      toggleMode();
    });
  </script>
@endsection

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Thêm QC</h5>
      <a href="{{ route('qc.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-2">Vui lòng kiểm tra lại dữ liệu:</div>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('qc.store') }}" method="POST" id="qc-form">
        @csrf

        <div class="row g-4">
          <div class="col-12">
            <label class="form-label d-block">Kiểu QC</label>
            <div class="btn-group" role="group" aria-label="Kiểu QC">
              <input type="radio" class="btn-check" name="qc_mode" id="mode-source" value="from_allocation"
                autocomplete="off" @checked(old('qc_mode', 'from_allocation') !== 'manual')>
              <label class="btn btn-outline-primary" for="mode-source">QC từ phân bổ may</label>

              <input type="radio" class="btn-check" name="qc_mode" id="mode-manual" value="manual" autocomplete="off"
                @checked(old('qc_mode') === 'manual')>
              <label class="btn btn-outline-primary" for="mode-manual">QC nhập tay</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="ngay_qc">Ngày QC <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('ngay_qc') is-invalid @enderror" id="ngay_qc"
              name="ngay_qc" value="{{ old('ngay_qc', now()->format('Y-m-d')) }}" required>
            @error('ngay_qc')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12" id="source-section">
            @error('allocation_groups')
              <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            <div class="d-flex flex-column gap-3" id="source-blocks"></div>
            <button type="button" class="btn btn-outline-primary mt-3" id="add-source-block">
              <i class="icon-base bx bx-plus me-1"></i> Thêm nguồn QC
            </button>
          </div>

          <div class="col-12 d-none" id="manual-section">
            @error('manual_groups')
              <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            <div class="d-flex flex-column gap-3" id="manual-blocks"></div>
            <button type="button" class="btn btn-outline-primary mt-3" id="add-product-block">
              <i class="icon-base bx bx-plus me-1"></i> Thêm mã hàng
            </button>
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
              <a href="{{ route('qc.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
