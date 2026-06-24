@extends('layouts/contentNavbarLayout')

@section('title', 'Thêm lần cắt')

@php
  $formatCatNumber = function ($value) {
      if ($value === null || $value === '') {
          return '';
      }

      $formatted = number_format((float) $value, 4, ',', '.');

      return rtrim(rtrim($formatted, '0'), ',');
  };

  $selectedDonHangId = old('don_hang_id');
  $catSubmitToken = old('cat_submit_token', (string) Str::uuid());
  $oldFixedItems = old('items', []);
  $selectedMatHangId = old('mat_hang_id');

  $oldChiTiets = collect(old('chi_tiets', []))
      ->filter(fn($item) => is_array($item) && isset($item['don_hang_chi_tiet_id']))
      ->keyBy(fn($item) => (string) $item['don_hang_chi_tiet_id']);

  $donHangData = $donHangs
      ->mapWithKeys(function ($donHang) use ($oldChiTiets) {
          return [
              (string) $donHang->id => [
                  'ma_don' => $donHang->ma_don,
                  'ma_kh' => $donHang->ma_kh,
                  'han_giao' => $donHang->han_giao ? $donHang->han_giao->format('d/m/Y') : '-',
                  'kenh_ban' => $donHang->kenh_ban ?: '-',
                  'chi_tiets' => $donHang->chiTiets
                      ->filter(fn($chiTiet) => (float) ($chiTiet->so_luong_can_cat ?? 0) > 0)
                      ->map(function ($chiTiet) use ($oldChiTiets) {
                          $oldChiTiet = $oldChiTiets->get((string) $chiTiet->id);

                          return [
                              'id' => $chiTiet->id,
                              'ma_hang' => $chiTiet->matHang?->ma_hang,
                              'ten_hang' => $chiTiet->matHang?->ten_hang,
                              'ten_mau' => $chiTiet->mau?->ten_mau,
                              'ten_size' => $chiTiet->size?->ten_size,
                              'so_luong_dat' => $chiTiet->so_luong_dat,
                              'so_luong_da_cat' => $chiTiet->so_luong_da_cat,
                              'so_luong_can_cat' => $chiTiet->so_luong_can_cat,
                              'old_so_luong_cat' => $oldChiTiet['so_luong_cat'] ?? null,
                          ];
                      })
                      ->values()
                      ->all(),
              ],
          ];
      })
      ->all();
@endphp

@section('page-script')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('cat-form');
      const donHangSelect = document.getElementById('don_hang_id');
      const orderInfo = document.getElementById('order-info');
      const orderDetails = document.getElementById('order-detail-wrapper');
      const orderDetailBody = document.getElementById('order-detail-body');
      const orderEmptyMessage = document.getElementById('order-empty-message');
      const fixedFields = document.getElementById('fixed-cat-fields');
      const soLuongInput = document.getElementById('so_luong_cat');
      const dinhMucInput = document.getElementById('dinh_muc');
      const previewInput = document.getElementById('vai_tieu_hao_display');
      const fixedDetailBody = document.getElementById('fixed-detail-body');
      const fixedEmptyMessage = document.getElementById('fixed-empty-message');
      const matHangSelect = document.getElementById('mat_hang_id');
      const orderData = @json($donHangData);
      const fixedItems = @json($oldFixedItems);
      const fixedItemOptions = @json($fixedItemOptions);
      const initialMatHangId = @json((string) $selectedMatHangId);

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

      function updateFixedRowFabric(row) {
        if (!row) {
          return;
        }

        const quantityInput = row.querySelector('.js-fixed-cut-quantity');
        const displayInput = row.querySelector('.js-fixed-fabric-display');
        const hiddenInput = row.querySelector('.js-fixed-fabric-hidden');

        if (!quantityInput || !displayInput || !hiddenInput) {
          return;
        }

        const quantity = Number(normalizeNumber(quantityInput.value) || 0);
        const dinhMuc = Number(normalizeNumber(dinhMucInput?.value) || 0);
        const fabric = quantity * dinhMuc;

        displayInput.value = fabric > 0 ? `${formatDisplayNumber(String(fabric))} m` : '0';
        hiddenInput.value = fabric > 0 ? String(fabric) : '0';
      }

      function renderFixedRows() {
        if (!fixedDetailBody) {
          return;
        }

        const matHangId = matHangSelect?.value || '';
        const rows = fixedItemOptions[matHangId] || [];
        const oldValues = String(matHangId) === initialMatHangId ?
          new Map(
            fixedItems.map(function(item) {
              return [`${item.mau_id}:${item.size_id}`, item.so_luong_cat || ''];
            })
          ) :
          new Map();

        fixedDetailBody.innerHTML = '';
        fixedEmptyMessage?.classList.toggle('d-none', rows.length > 0 || !matHangId);

        rows.forEach(function(item, index) {
          const row = document.createElement('tr');
          const oldValue = oldValues.get(`${item.mau_id}:${item.size_id}`) || '';

          row.innerHTML = `
            <td>
              ${item.ten_mau || '-'}
              <input type="hidden" name="items[${index}][mau_id]" value="${item.mau_id}">
            </td>
            <td>
              ${item.ten_size || '-'}
              <input type="hidden" name="items[${index}][size_id]" value="${item.size_id}">
            </td>
            <td>
              <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control js-number-format js-fixed-cut-quantity"
                name="items[${index}][so_luong_cat]"
                value="${formatDisplayNumber(oldValue)}"
                placeholder="Nhập số lượng">
            </td>
            <td>
              <input type="text" class="form-control js-fixed-fabric-display" value="0" readonly>
              <input type="hidden" class="js-fixed-fabric-hidden" name="items[${index}][vai_tieu_hao]" value="0">
            </td>
          `;

          fixedDetailBody.appendChild(row);
          wireNumberInput(row.querySelector('.js-number-format'));
          updateFixedRowFabric(row);
        });
      }

      function updateOrderRowDiff(row) {
        if (!row) {
          return;
        }

        const input = row.querySelector('.js-order-cut-quantity');
        const diffElement = row.querySelector('.js-order-cut-diff');

        if (!input || !diffElement) {
          return;
        }

        const quantity = Number(normalizeNumber(input.value) || 0);
        const reference = Number(input.dataset.referenceQuantity || 0);
        const diff = quantity - reference;
        const absDiff = Math.abs(diff);

        if (diff < 0) {
          diffElement.innerHTML = `<span class="badge bg-warning text-dark">Cắt thiếu ${formatDisplayNumber(absDiff)}</span>`;
        } else if (diff > 0) {
          diffElement.innerHTML = `<span class="badge bg-info text-dark">Cắt thừa ${formatDisplayNumber(absDiff)}</span>`;
        } else {
          diffElement.innerHTML = '<span class="badge bg-success">Cắt đủ</span>';
        }
      }

      function wireNumberInput(input) {
        input.addEventListener('input', function() {
          input.value = input.value.replace(/[^\d.,]/g, '');
          updatePreview();
          updateOrderRowDiff(input.closest('tr'));
          updateFixedRowFabric(input.closest('tr'));
        });

        input.addEventListener('focus', function() {
          input.value = formatEditableNumber(input.value);
        });

        input.addEventListener('blur', function() {
          input.value = formatDisplayNumber(input.value);
          updatePreview();
          updateOrderRowDiff(input.closest('tr'));
          updateFixedRowFabric(input.closest('tr'));
        });

        input.value = formatDisplayNumber(input.value);
        updateOrderRowDiff(input.closest('tr'));
      }

      function updatePreview() {
        if (soLuongInput && dinhMucInput && previewInput) {
          const soLuong = Number(normalizeNumber(soLuongInput.value) || 0);
          const dinhMuc = Number(normalizeNumber(dinhMucInput.value) || 0);

          previewInput.value = soLuong && dinhMuc ? formatDisplayNumber(String(soLuong * dinhMuc)) + ' m' : '-';
        }

        document.querySelectorAll('#fixed-detail-body tr').forEach(updateFixedRowFabric);
      }

      function setOrderInfo(order) {
        document.getElementById('don_hang_ma_don').value = order ? order.ma_don : '-';
        document.getElementById('don_hang_ma_kh').value = order ? order.ma_kh : '-';
        document.getElementById('don_hang_han_giao').value = order ? order.han_giao : '-';
        document.getElementById('don_hang_kenh_ban').value = order ? order.kenh_ban : '-';
      }

      function renderOrderDetails(order) {
        orderDetailBody.innerHTML = '';

        if (!order) {
          if (orderEmptyMessage) {
            orderEmptyMessage.classList.add('d-none');
          }

          return;
        }

        if (order.chi_tiets.length === 0) {
          if (orderEmptyMessage) {
            orderEmptyMessage.classList.remove('d-none');
          }

          return;
        }

        if (orderEmptyMessage) {
          orderEmptyMessage.classList.add('d-none');
        }

        order.chi_tiets.forEach(function(item, index) {
          const row = document.createElement('tr');
          const oldValue = item.old_so_luong_cat !== null && item.old_so_luong_cat !== undefined ?
            item.old_so_luong_cat :
            item.so_luong_can_cat;

          row.innerHTML = `
            <td>
              <strong>${item.ma_hang || '-'}</strong>
              <div class="text-muted small">${item.ten_hang || '-'}</div>
              <input type="hidden" name="chi_tiets[${index}][don_hang_chi_tiet_id]" value="${item.id}">
            </td>
            <td>${item.ten_mau || '-'}</td>
            <td>${item.ten_size || '-'}</td>
            <td>${formatDisplayNumber(item.so_luong_dat)}</td>
            <td>${formatDisplayNumber(item.so_luong_da_cat)}</td>
            <td>${formatDisplayNumber(item.so_luong_can_cat)}</td>
            <td>
              <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control js-number-format js-order-cut-quantity"
                name="chi_tiets[${index}][so_luong_cat]"
                data-reference-quantity="${item.so_luong_can_cat || 0}"
                value="${formatDisplayNumber(oldValue)}">
            </td>
            <td class="js-order-cut-diff"></td>
          `;

          orderDetailBody.appendChild(row);
          wireNumberInput(row.querySelector('.js-number-format'));
        });
      }

      function toggleMode() {
        const orderId = donHangSelect ? donHangSelect.value : '';
        const order = orderId ? orderData[orderId] : null;
        const hasOrder = Boolean(order);

        if (orderInfo) {
          orderInfo.classList.toggle('d-none', !hasOrder);
        }
        if (orderDetails) {
          orderDetails.classList.toggle('d-none', !hasOrder);
        }
        if (fixedFields) {
          fixedFields.classList.toggle('d-none', hasOrder);
        }

        fixedFields?.querySelectorAll('select, input').forEach(function(input) {
          input.disabled = hasOrder;
        });

        setOrderInfo(order);
        renderOrderDetails(order);
        updatePreview();
      }

      document.querySelectorAll('.js-number-format').forEach(wireNumberInput);

      if (matHangSelect) {
        matHangSelect.addEventListener('change', renderFixedRows);
        renderFixedRows();
      }

      if (dinhMucInput) {
        dinhMucInput.addEventListener('input', updatePreview);
        dinhMucInput.addEventListener('blur', updatePreview);
      }

      if (donHangSelect) {
        donHangSelect.addEventListener('change', toggleMode);
        toggleMode();
      }

      if (form) {
        form.addEventListener('submit', function() {
          form.querySelectorAll('.js-number-format').forEach(function(input) {
            input.value = normalizeNumber(input.value);
          });
          document.querySelectorAll('#fixed-detail-body tr').forEach(updateFixedRowFabric);

          form.querySelectorAll('button[type="submit"]').forEach(function(button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Đang lưu';
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
      <h5 class="mb-0">Thêm lần cắt</h5>
      <a href="{{ route('cat.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>

    <div class="card-body">
      <form action="{{ route('cat.store') }}" method="POST" id="cat-form">
        @csrf
        <input type="hidden" name="cat_submit_token" value="{{ $catSubmitToken }}">

        <div class="row g-4">
          <div class="col-12">
            <label class="form-label" for="don_hang_id">Mã đơn hàng</label>
            <select class="form-select @error('don_hang_id') is-invalid @enderror" id="don_hang_id" name="don_hang_id">
              <option value="">-- Cắt cố định / không chọn đơn hàng --</option>
              @foreach ($donHangs as $donHang)
                <option value="{{ $donHang->id }}" @selected((string) $selectedDonHangId === (string) $donHang->id)>
                  {{ $donHang->ma_don }} - {{ $donHang->ma_kh }}
                </option>
              @endforeach
            </select>
            @error('don_hang_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 d-none" id="order-info">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Mã đơn</label>
                <input type="text" class="form-control" id="don_hang_ma_don" value="-" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Mã KH</label>
                <input type="text" class="form-control" id="don_hang_ma_kh" value="-" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Hạn giao</label>
                <input type="text" class="form-control" id="don_hang_han_giao" value="-" readonly>
              </div>
              <div class="col-md-3">
                <label class="form-label">Kênh bán</label>
                <input type="text" class="form-control" id="don_hang_kenh_ban" value="-" readonly>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="ngay_cat">Ngày cắt <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('ngay_cat') is-invalid @enderror" id="ngay_cat"
              name="ngay_cat" value="{{ old('ngay_cat') }}" required>
            @error('ngay_cat')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="ban_cat_ten">Bàn cắt <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('ban_cat_ten') is-invalid @enderror" id="ban_cat_ten"
              name="ban_cat_ten" value="{{ old('ban_cat_ten') }}" maxlength="255" required>
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
                <option value="{{ $donViCat->id }}" @selected(old('don_vi_cat_id') == $donViCat->id)>
                  {{ $donViCat->ten_don_vi }}
                </option>
              @endforeach
            </select>
            @error('don_vi_cat_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label class="form-label" for="dinh_muc">Định mức <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" inputmode="decimal" autocomplete="off"
                class="form-control js-number-format @error('dinh_muc') is-invalid @enderror" id="dinh_muc"
                name="dinh_muc" value="{{ $formatCatNumber(old('dinh_muc')) }}" required>
              <span class="input-group-text">m</span>
              @error('dinh_muc')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="col-12">
            <label class="form-label" for="ghi_chu">Ghi chú</label>
            <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="3">{{ old('ghi_chu') }}</textarea>
            @error('ghi_chu')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12 d-none" id="order-detail-wrapper">
            @error('chi_tiets')
              <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror
            <div class="alert alert-info py-2 d-none" id="order-empty-message">
              Đơn hàng này không còn dòng nào cần cắt.
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Mã hàng / Tên hàng</th>
                    <th>Màu</th>
                    <th>Size</th>
                    <th>SL đặt</th>
                    <th>Đã cắt</th>
                    <th>SL cần cắt tham chiếu</th>
                    <th style="width: 180px;">SL cắt thực tế</th>
                    <th>Chênh lệch sau cắt</th>
                  </tr>
                </thead>
                <tbody id="order-detail-body"></tbody>
              </table>
            </div>
            <div class="form-text mt-2">
              SL cần cắt chỉ là số tham chiếu theo đơn hàng. Có thể nhập SL cắt thực tế nhỏ hơn hoặc lớn hơn nếu thực tế cắt thiếu/thừa.
            </div>
          </div>

          <div class="col-12" id="fixed-cat-fields">
            <div class="row g-4">
              <div class="col-md-4">
                <label class="form-label" for="mat_hang_id">Mặt hàng <span class="text-danger">*</span></label>
                <select class="form-select @error('mat_hang_id') is-invalid @enderror" id="mat_hang_id"
                  name="mat_hang_id" required>
                  <option value="">-- Chọn mặt hàng --</option>
                  @foreach ($matHangs as $matHang)
                    <option value="{{ $matHang->id }}" @selected((string) $selectedMatHangId === (string) $matHang->id)>
                      {{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}
                    </option>
                  @endforeach
                </select>
                @error('mat_hang_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <div class="border rounded p-3">
                  <h6 class="mb-3">Chi tiết cắt</h6>

                  @error('items')
                    <div class="alert alert-danger py-2">{{ $message }}</div>
                  @enderror

                  <div class="alert alert-info py-2 d-none" id="fixed-empty-message">
                    Mặt hàng này chưa có thông tin màu và size.
                  </div>

                  <div class="table-responsive">
                    <table class="table align-middle mb-0">
                      <thead>
                        <tr>
                          <th style="min-width: 180px;">Màu</th>
                          <th style="min-width: 160px;">Size</th>
                          <th style="min-width: 160px;">Số lượng cắt</th>
                          <th style="min-width: 160px;">Vải tiêu hao</th>
                        </tr>
                      </thead>
                      <tbody id="fixed-detail-body"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary">
                <i class="icon-base bx bx-save me-1"></i> Lưu
              </button>
              <a href="{{ route('cat.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
