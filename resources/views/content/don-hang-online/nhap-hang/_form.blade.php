@php
  $details = old('chi_tiets', isset($import) ? $import->chiTiets->map(fn ($item) => [
    'ten_san_pham' => $item->ten_san_pham,
    'mau' => $item->mau,
    'size' => $item->size,
    'so_luong' => $item->so_luong,
    'don_gia' => $item->don_gia,
  ])->all() : [['ten_san_pham' => '', 'mau' => '', 'size' => '', 'so_luong' => 1, 'don_gia' => '']]);
  $productGroups = collect($details)
    ->groupBy(fn ($detail) => trim((string) ($detail['ten_san_pham'] ?? '')))
    ->map(fn ($items, $product) => ['ten_san_pham' => $product, 'chi_tiets' => $items->values()])
    ->values();
@endphp

@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Thông tin nhập hàng</h5></div>
  <div class="card-body row g-3">
    <div class="col-md-3">
      <label class="form-label">Ngày nhập <span class="text-danger">*</span></label>
      <input type="date" name="ngay_nhap" class="form-control" required value="{{ old('ngay_nhap', isset($import) ? $import->ngay_nhap?->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Sản phẩm nhập</h5>
  </div>
  <div class="card-body">
    <div id="product-groups" class="d-flex flex-column gap-4">
      @foreach ($productGroups as $groupIndex => $group)
        <div class="border rounded p-3 js-product-group">
          <div class="row g-3 align-items-end mb-3">
            <div class="col-md-10">
              <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
              <input class="form-control js-product-name" required value="{{ $group['ten_san_pham'] }}">
            </div>
            <div class="col-md-2 text-md-end">
              <button type="button" class="btn btn-sm btn-outline-danger js-remove-product"><i class="icon-base bx bx-trash me-1"></i>Xóa SP</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead><tr><th style="min-width:140px">Màu</th><th style="min-width:120px">Size</th><th style="width:140px">SL <span class="text-danger">*</span></th><th style="width:180px">Đơn giá/1 cái <span class="text-danger">*</span></th><th class="text-end" style="width:180px">Thành tiền</th><th style="width:70px"></th></tr></thead>
              <tbody>
                @foreach ($group['chi_tiets'] as $detail)
                  @php $index = $groupIndex.'_'.$loop->index; @endphp
                  <tr>
                    <td><input type="hidden" class="js-row-product" name="chi_tiets[{{ $index }}][ten_san_pham]" required value="{{ $group['ten_san_pham'] }}"><input class="form-control" name="chi_tiets[{{ $index }}][mau]" value="{{ $detail['mau'] ?? '' }}"></td>
                    <td><input class="form-control" name="chi_tiets[{{ $index }}][size]" value="{{ $detail['size'] ?? '' }}"></td>
                    <td><input type="number" min="0.0001" step="0.0001" class="form-control js-qty" name="chi_tiets[{{ $index }}][so_luong]" required value="{{ $detail['so_luong'] ?? 1 }}"></td>
                    <td><input type="text" inputmode="numeric" class="form-control js-price" name="chi_tiets[{{ $index }}][don_gia]" required value="{{ isset($detail['don_gia']) ? number_format((float) $detail['don_gia'], 0, ',', '.') : '' }}"></td>
                    <td class="text-end fw-semibold js-line-total">0 ₫</td>
                    <td><button type="button" class="btn btn-sm btn-icon btn-outline-danger js-remove"><i class="icon-base bx bx-trash"></i></button></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endforeach
    </div>

    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center border-top pt-3 mt-4">
      <div class="fw-semibold">Tổng: <span id="total-qty">0</span> cái - <span id="total-money">0 ₫</span></div>
      <div class="d-flex flex-wrap gap-2 justify-content-md-end">
        <button type="button" class="btn btn-outline-primary" id="add-detail"><i class="icon-base bx bx-plus me-1"></i>Thêm màu/size</button>
        <button type="button" class="btn btn-outline-success" id="add-product"><i class="icon-base bx bx-package me-1"></i>Thêm sản phẩm</button>
      </div>
    </div>
  </div>
</div>

<div class="d-flex gap-2 justify-content-end mt-4">
  <a href="{{ route('nhap-hang-online.index') }}" class="btn btn-outline-secondary">Hủy</a>
  <button class="btn btn-primary" type="submit">Lưu</button>
</div>

@section('page-script')
@parent
<script>
(() => {
  const groups = document.querySelector('#product-groups');
  const parseMoney = value => Number(String(value || '').replace(/[^0-9-]/g, '')) || 0;
  const formatMoney = value => new Intl.NumberFormat('vi-VN').format(value);
  const escapeAttr = value => String(value || '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
  const updateGroupProduct = group => {
    const productName = group.querySelector('.js-product-name').value;
    group.querySelectorAll('.js-row-product').forEach(input => input.value = productName);
  };
  const recalcRow = row => {
    const total = (Number(row.querySelector('.js-qty').value) || 0) * parseMoney(row.querySelector('.js-price').value);
    row.querySelector('.js-line-total').textContent = formatMoney(total) + ' ₫';
    return total;
  };
  const totals = () => {
    let qty = 0, money = 0;
    groups.querySelectorAll('tbody tr').forEach(row => {
      qty += Number(row.querySelector('.js-qty').value) || 0;
      money += recalcRow(row);
    });
    document.querySelector('#total-qty').textContent = formatMoney(qty);
    document.querySelector('#total-money').textContent = formatMoney(money) + ' ₫';
  };
  const bind = row => {
    row.querySelector('.js-remove').addEventListener('click', () => {
      if (groups.querySelectorAll('tbody tr').length > 1) row.remove();
      totals();
    });
    row.querySelector('.js-price').addEventListener('input', e => { e.target.value = formatMoney(parseMoney(e.target.value)); totals(); });
    row.querySelector('.js-qty').addEventListener('input', totals);
  };
  const bindGroup = group => {
    group.querySelector('.js-product-name').addEventListener('input', () => updateGroupProduct(group));
    group.querySelector('.js-remove-product').addEventListener('click', () => {
      if (groups.querySelectorAll('.js-product-group').length > 1) group.remove();
      totals();
    });
    group.querySelectorAll('tbody tr').forEach(bind);
    updateGroupProduct(group);
  };
  const variantRow = (index, productName = '') => `<tr><td><input type="hidden" class="js-row-product" name="chi_tiets[${index}][ten_san_pham]" required value="${escapeAttr(productName)}"><input class="form-control" name="chi_tiets[${index}][mau]"></td><td><input class="form-control" name="chi_tiets[${index}][size]"></td><td><input type="number" min="0.0001" step="0.0001" class="form-control js-qty" name="chi_tiets[${index}][so_luong]" required value="1"></td><td><input type="text" inputmode="numeric" class="form-control js-price" name="chi_tiets[${index}][don_gia]" required></td><td class="text-end fw-semibold js-line-total">0 ₫</td><td><button type="button" class="btn btn-sm btn-icon btn-outline-danger js-remove"><i class="icon-base bx bx-trash"></i></button></td></tr>`;
  const productGroup = index => {
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded p-3 js-product-group';
    wrapper.innerHTML = `<div class="row g-3 align-items-end mb-3"><div class="col-md-10"><label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label><input class="form-control js-product-name" required></div><div class="col-md-2 text-md-end"><button type="button" class="btn btn-sm btn-outline-danger js-remove-product"><i class="icon-base bx bx-trash me-1"></i>Xóa SP</button></div></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th style="min-width:140px">Màu</th><th style="min-width:120px">Size</th><th style="width:140px">SL <span class="text-danger">*</span></th><th style="width:180px">Đơn giá/1 cái <span class="text-danger">*</span></th><th class="text-end" style="width:180px">Thành tiền</th><th style="width:70px"></th></tr></thead><tbody>${variantRow(index)}</tbody></table></div>`;
    return wrapper;
  };
  groups.querySelectorAll('.js-product-group').forEach(bindGroup);
  document.querySelector('#add-detail').addEventListener('click', () => {
    const index = Date.now();
    const group = groups.querySelector('.js-product-group:last-child');
    const body = group.querySelector('tbody');
    const template = document.createElement('template');
    template.innerHTML = variantRow(index, group.querySelector('.js-product-name').value);
    const row = template.content.firstElementChild;
    body.appendChild(row); bind(row); totals();
  });
  document.querySelector('#add-product').addEventListener('click', () => {
    const group = productGroup(Date.now());
    groups.appendChild(group);
    bindGroup(group);
    group.querySelector('.js-product-name').focus();
    totals();
  });
  document.querySelector('form').addEventListener('submit', () => {
    groups.querySelectorAll('.js-product-group').forEach(updateGroupProduct);
    groups.querySelectorAll('.js-price').forEach(input => input.value = parseMoney(input.value));
  });
  totals();
})();
</script>
@endsection
