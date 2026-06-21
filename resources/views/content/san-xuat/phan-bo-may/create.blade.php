@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm phân bổ may')

@php
  $initialState = [
      'don_hang_id' => old('don_hang_id'),
      'mat_hang_id' => old('mat_hang_id'),
      'size_ids' => old('size_ids', []),
  ];
  $phanBoMaySubmitToken = old('phan_bo_may_submit_token', (string) \Illuminate\Support\Str::uuid());
@endphp

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('phan-bo-may-form');
      const options = @json($allocationOptions);
      const modeInputs = Array.from(document.querySelectorAll('input[name="allocation_mode"]'));
      const donHangSearchInput = document.getElementById('don_hang_search');
      const donHangInput = document.getElementById('don_hang_id');
      const donHangList = document.getElementById('don_hang_options');
      const productSearchInput = document.getElementById('product_search');
      const matHangInput = document.getElementById('mat_hang_id');
      const productList = document.getElementById('product_options');
      const sizeBox = document.getElementById('size-box');
      const sizeList = document.getElementById('size-list');
      const tableWrapper = document.getElementById('allocation-table-wrapper');
      const allocationBody = document.getElementById('allocation-body');
      const initialState = @json($initialState);

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

      function wireNumberInput(input) {
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
      }

      function currentMode() {
        return document.querySelector('input[name="allocation_mode"]:checked')?.value || 'by_product';
      }

      function selectedDonHangId() {
        return donHangInput.value || '';
      }

      function selectedMatHangId() {
        return matHangInput.value || '';
      }

      function selectedSizeIds() {
        return Array.from(sizeList.querySelectorAll('input[type="checkbox"]:checked')).map(function(input) {
          return String(input.value);
        });
      }

      function filteredBaseOptions() {
        const orderId = selectedDonHangId();

        return options.filter(function(item) {
          return !orderId || String(item.don_hang_id || '') === orderId;
        });
      }

      function uniqueBy(items, keyGetter) {
        const map = new Map();

        items.forEach(function(item) {
          const key = keyGetter(item);
          if (!map.has(key)) {
            map.set(key, item);
          }
        });

        return Array.from(map.values());
      }

      function orderLabel(item) {
        if (!item.don_hang_id) {
          return '';
        }

        return `${item.ma_don || '-'} - ${item.ma_kh || '-'}`;
      }

      function productLabel(item) {
        return `${item.ma_hang || '-'} - ${item.ten_hang || '-'}`;
      }

      function refreshOrders() {
        const keyword = String(donHangSearchInput.value || '').trim().toLowerCase();
        const orders = uniqueBy(options.filter(function(item) {
          return item.don_hang_id;
        }), function(item) {
          return String(item.don_hang_id);
        }).filter(function(item) {
          const label = orderLabel(item).toLowerCase();
          return !keyword || label.includes(keyword);
        });

        donHangList.innerHTML = '';

        orders.forEach(function(item) {
          const option = document.createElement('option');
          option.value = orderLabel(item);
          donHangList.appendChild(option);
        });
      }

      function syncSelectedOrder() {
        const value = String(donHangSearchInput.value || '').trim();
        const order = uniqueBy(options.filter(item => item.don_hang_id), item => String(item.don_hang_id))
          .find(function(item) {
            return orderLabel(item) === value;
          });

        donHangInput.value = order ? order.don_hang_id : '';
      }

      function refreshProducts() {
        const keyword = String(productSearchInput.value || '').trim().toLowerCase();
        const currentValue = matHangInput.value;
        const products = uniqueBy(filteredBaseOptions(), function(item) {
          return String(item.mat_hang_id);
        }).filter(function(item) {
          const label = `${item.ma_hang || ''} ${item.ten_hang || ''}`.toLowerCase();
          return !keyword || label.includes(keyword);
        });

        productList.innerHTML = '';

        products.forEach(function(item) {
          const option = document.createElement('option');
          option.value = productLabel(item);
          productList.appendChild(option);
        });

        if (currentValue && products.some(item => String(item.mat_hang_id) === String(currentValue))) {
          return;
        }
      }

      function syncSelectedProduct() {
        const value = String(productSearchInput.value || '').trim();
        const product = uniqueBy(filteredBaseOptions(), item => String(item.mat_hang_id))
          .find(function(item) {
            return productLabel(item) === value;
          });

        matHangInput.value = product ? product.mat_hang_id : '';
      }

      function refreshSizes() {
        const mode = currentMode();
        const productId = selectedMatHangId();
        const sizes = uniqueBy(filteredBaseOptions().filter(function(item) {
          return !productId || String(item.mat_hang_id) === String(productId);
        }), function(item) {
          return String(item.size_id);
        });

        sizeBox.classList.toggle('d-none', mode !== 'by_size');
        sizeList.innerHTML = '';

        sizes.forEach(function(item) {
          const label = document.createElement('label');
          label.className = 'form-check form-check-inline mb-2';
          label.innerHTML = `
            <input class="form-check-input" type="checkbox" name="size_ids[]" value="${item.size_id}">
            <span class="form-check-label">${item.ten_size || '-'}</span>
          `;
          sizeList.appendChild(label);
        });
      }

      function selectedRows() {
        const mode = currentMode();
        const orderId = selectedDonHangId();
        const productId = selectedMatHangId();
        const sizeIds = selectedSizeIds();

        if (!productId) {
          return [];
        }

        if (mode === 'by_size' && sizeIds.length === 0) {
          return [];
        }

        return filteredBaseOptions().filter(function(item) {
          const isMatched = (!orderId || String(item.don_hang_id || '') === orderId) &&
            String(item.mat_hang_id) === String(productId);

          if (!isMatched) {
            return false;
          }

          if (mode === 'by_product') {
            return true;
          }

          return sizeIds.includes(String(item.size_id));
        }).sort(function(a, b) {
          return String(a.ten_size || '').localeCompare(String(b.ten_size || '')) ||
            String(a.ten_mau || '').localeCompare(String(b.ten_mau || ''));
        });
      }

      function renderTable() {
        const rows = selectedRows();
        allocationBody.innerHTML = '';
        tableWrapper.classList.toggle('d-none', rows.length === 0);

        rows.forEach(function(item, index) {
          const row = document.createElement('tr');

          row.innerHTML = `
            <td>${item.ten_mau || '-'}</td>
            <td>
              ${item.ten_size || '-'}
              <input type="hidden" name="allocations[${index}][group_key]" value="${item.key || ''}">
              <input type="hidden" name="allocations[${index}][don_hang_chi_tiet_id]" value="${item.don_hang_chi_tiet_id || ''}">
              <input type="hidden" name="allocations[${index}][mat_hang_id]" value="${item.mat_hang_id || ''}">
              <input type="hidden" name="allocations[${index}][mau_id]" value="${item.mau_id || ''}">
              <input type="hidden" name="allocations[${index}][size_id]" value="${item.size_id || ''}">
            </td>
            <td>${formatDisplayNumber(item.sl_cat)}</td>
            <td>${formatDisplayNumber(item.allocated)}</td>
            <td>${formatDisplayNumber(item.remaining)}</td>
            <td>
              <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control js-number-format"
                name="allocations[${index}][so_luong_giao]"
                value="${formatDisplayNumber(item.remaining)}">
            </td>
          `;

          allocationBody.appendChild(row);
          wireNumberInput(row.querySelector('.js-number-format'));
        });
      }

      function refreshAll() {
        refreshProducts();
        refreshSizes();
        renderTable();
      }

      modeInputs.forEach(function(input) {
        input.addEventListener('change', function() {
          refreshSizes();
          renderTable();
        });
      });

      donHangSearchInput.addEventListener('input', function() {
        refreshOrders();
        syncSelectedOrder();
        productSearchInput.value = '';
        matHangInput.value = '';
        refreshAll();
      });

      donHangSearchInput.addEventListener('blur', function() {
        syncSelectedOrder();
        refreshAll();
      });

      productSearchInput.addEventListener('input', function() {
        refreshProducts();
        syncSelectedProduct();
        refreshSizes();
        renderTable();
      });

      productSearchInput.addEventListener('blur', function() {
        syncSelectedProduct();
        refreshSizes();
        renderTable();
      });
      sizeList.addEventListener('change', renderTable);

      if (form) {
        form.addEventListener('submit', function() {
          form.querySelectorAll('.js-number-format').forEach(function(input) {
            input.value = normalizeNumber(input.value);
          });

          form.querySelectorAll('button[type="submit"]').forEach(function(button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Đang lưu';
          });
        });
      }

      if (initialState.don_hang_id) {
        const order = options.find(function(item) {
          return String(item.don_hang_id || '') === String(initialState.don_hang_id);
        });

        donHangSearchInput.value = order ? orderLabel(order) : '';
        donHangInput.value = initialState.don_hang_id;
      }

      refreshOrders();
      refreshProducts();

      if (initialState.mat_hang_id) {
        const product = filteredBaseOptions().find(function(item) {
          return String(item.mat_hang_id || '') === String(initialState.mat_hang_id);
        });

        productSearchInput.value = product ? productLabel(product) : '';
        matHangInput.value = initialState.mat_hang_id;
      }

      refreshSizes();

      if (Array.isArray(initialState.size_ids)) {
        initialState.size_ids.forEach(function(sizeId) {
          const checkbox = sizeList.querySelector(`input[value="${sizeId}"]`);

          if (checkbox) {
            checkbox.checked = true;
          }
        });
      }

      renderTable();
    });
  </script>
@endsection

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Thêm phân bổ may</h5>
      <a href="{{ route('phan-bo-may.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('phan-bo-may.store') }}" method="POST" id="phan-bo-may-form">
        @csrf
        <input type="hidden" name="phan_bo_may_submit_token" value="{{ $phanBoMaySubmitToken }}">

        <div class="row g-4">
          <div class="col-12">
            <label class="form-label d-block">Kiểu phân bổ <span class="text-danger">*</span></label>
            <div class="btn-group" role="group" aria-label="Kiểu phân bổ">
              <input type="radio" class="btn-check" name="allocation_mode" id="mode-by-product" value="by_product"
                autocomplete="off" @checked(old('allocation_mode', 'by_product') === 'by_product')>
              <label class="btn btn-outline-primary" for="mode-by-product">Phân bổ theo mã hàng</label>

              <input type="radio" class="btn-check" name="allocation_mode" id="mode-by-size" value="by_size"
                autocomplete="off" @checked(old('allocation_mode') === 'by_size')>
              <label class="btn btn-outline-primary" for="mode-by-size">Phân bổ theo size</label>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="don_hang_search">Mã đơn</label>
            <input type="text" class="form-control @error('don_hang_id') is-invalid @enderror" id="don_hang_search"
              list="don_hang_options" autocomplete="off" placeholder="Gõ mã đơn hoặc mã KH">
            <input type="hidden" id="don_hang_id" name="don_hang_id" value="{{ old('don_hang_id') }}">
            <datalist id="don_hang_options"></datalist>
            @error('don_hang_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="product_search">Mã hàng <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('mat_hang_id') is-invalid @enderror" id="product_search"
              list="product_options" autocomplete="off" placeholder="Gõ mã hàng hoặc tên hàng">
            <input type="hidden" id="mat_hang_id" name="mat_hang_id" value="{{ old('mat_hang_id') }}">
            <datalist id="product_options"></datalist>
            @error('mat_hang_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-8 d-none" id="size-box">
            <label class="form-label d-block">Size <span class="text-danger">*</span></label>
            <div class="border rounded p-3" id="size-list"></div>
            @error('size_ids')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="don_vi_may_id">Đơn vị may <span class="text-danger">*</span></label>
            <select class="form-select @error('don_vi_may_id') is-invalid @enderror" id="don_vi_may_id"
              name="don_vi_may_id" required>
              <option value="">-- Chọn đơn vị may --</option>
              @foreach ($donViMays as $donViMay)
                <option value="{{ $donViMay->id }}" @selected(old('don_vi_may_id') == $donViMay->id)>
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
              name="ngay_phan_bo" value="{{ old('ngay_phan_bo', now()->format('Y-m-d')) }}" required>
            @error('ngay_phan_bo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 d-none" id="allocation-table-wrapper">
            @error('allocations')
              <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Màu</th>
                    <th>Size</th>
                    <th>SL cắt</th>
                    <th>Đã phân bổ</th>
                    <th>Còn lại</th>
                    <th style="width: 180px;">SL giao</th>
                  </tr>
                </thead>
                <tbody id="allocation-body"></tbody>
              </table>
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
              <a href="{{ route('phan-bo-may.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
