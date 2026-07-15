@php
  $formatQuantity = function ($value) {
    $number = (float) str_replace(',', '.', (string) $value);
    $formatted = rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');

    return $formatted !== '' ? $formatted : '0';
  };
  $details = old('chi_tiets', isset($order) ? $order->chiTiets->map(fn ($item) => [
    'mau' => $item->mau, 'size' => $item->size, 'so_luong' => $item->so_luong, 'thanh_tien' => $item->thanh_tien, 'nguon' => $item->nguon,
  ])->all() : [['mau' => '', 'size' => '', 'so_luong' => 1, 'thanh_tien' => '', 'nguon' => 'thu_cong']]);
  $selectedChannel = old('kenh_ban', isset($order) && in_array($order->kenh_ban ?? '', ['Tiktok', 'Shopee', 'Bán sỉ'], true) ? $order->kenh_ban : 'Tiktok');
@endphp

@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Thông tin chung</h5></div>
  <div class="card-body row g-3">
    <div class="col-md-3">
      <label class="form-label">Ngày xuất <span class="text-danger">*</span></label>
      <input type="date" name="ngay_hoan_thanh" class="form-control" required
        value="{{ old('ngay_hoan_thanh', isset($order) ? $order->ngay_hoan_thanh?->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
      <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
      <input type="text" name="ten_san_pham" class="form-control" maxlength="500" required
        value="{{ old('ten_san_pham', $order->ten_san_pham ?? '') }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Kênh bán <span class="text-danger">*</span></label>
      <select name="kenh_ban" class="form-select" required>
        <option value="Tiktok" @selected($selectedChannel === 'Tiktok')>Tiktok</option>
        <option value="Shopee" @selected($selectedChannel === 'Shopee')>Shopee</option>
        <option value="Bán sỉ" @selected($selectedChannel === 'Bán sỉ')>Bán sỉ</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Ghi chú</label>
      <textarea name="ghi_chu" class="form-control" rows="2">{{ old('ghi_chu', $order->ghi_chu ?? '') }}</textarea>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Màu / Size</h5>
    <button type="button" class="btn btn-sm btn-outline-primary" id="add-detail"><i class="icon-base bx bx-plus me-1"></i>Thêm dòng</button>
  </div>
  <div class="table-responsive">
    <table class="table align-middle" id="detail-table">
      <thead><tr><th>Màu</th><th>Size</th><th style="width:160px">Số lượng <span class="text-danger">*</span></th><th style="width:220px">Thành tiền <span class="text-danger">*</span></th><th style="width:70px"></th></tr></thead>
      <tbody>
        @foreach ($details as $index => $detail)
          <tr>
            <td><input type="hidden" name="chi_tiets[{{ $index }}][nguon]" value="{{ $detail['nguon'] ?? 'thu_cong' }}"><input class="form-control" name="chi_tiets[{{ $index }}][mau]" value="{{ $detail['mau'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][size]" value="{{ $detail['size'] ?? '' }}"></td>
            <td><input type="number" min="0.0001" step="0.0001" class="form-control js-qty" name="chi_tiets[{{ $index }}][so_luong]" required value="{{ $formatQuantity($detail['so_luong'] ?? 1) }}"></td>
            <td><input type="text" inputmode="numeric" class="form-control js-money" name="chi_tiets[{{ $index }}][thanh_tien]" required value="{{ isset($detail['thanh_tien']) ? number_format((float) $detail['thanh_tien'], 0, ',', '.') : '' }}"></td>
            <td><button type="button" class="btn btn-sm btn-icon btn-outline-danger js-remove"><i class="icon-base bx bx-trash"></i></button></td>
          </tr>
        @endforeach
      </tbody>
      <tfoot><tr><th colspan="2" class="text-end">Tổng</th><th id="total-qty">0</th><th id="total-money">0 ₫</th><th></th></tr></tfoot>
    </table>
  </div>
</div>

<div class="d-flex gap-2 justify-content-end mt-4">
  <a href="{{ route('don-hang-hoan-thanh.index') }}" class="btn btn-outline-secondary">Hủy</a>
  <button class="btn btn-primary" type="submit">Lưu</button>
</div>

@section('page-script')
@parent
<script>
(() => {
  const body = document.querySelector('#detail-table tbody');
  const parseMoney = value => Number(String(value || '').replace(/[^0-9-]/g, '')) || 0;
  const formatMoney = value => new Intl.NumberFormat('vi-VN').format(value);
  const totals = () => {
    let qty = 0, money = 0;
    body.querySelectorAll('tr').forEach(row => { qty += Number(row.querySelector('.js-qty').value) || 0; money += parseMoney(row.querySelector('.js-money').value); });
    document.querySelector('#total-qty').textContent = new Intl.NumberFormat('vi-VN').format(qty);
    document.querySelector('#total-money').textContent = formatMoney(money) + ' ₫';
  };
  const bind = row => {
    row.querySelector('.js-remove').addEventListener('click', () => { if (body.rows.length > 1) row.remove(); totals(); });
    row.querySelector('.js-money').addEventListener('input', e => { const caret = e.target.selectionStart; e.target.value = formatMoney(parseMoney(e.target.value)); e.target.setSelectionRange(caret, caret); totals(); });
    row.querySelector('.js-qty').addEventListener('input', totals);
  };
  [...body.rows].forEach(bind);
  document.querySelector('#add-detail').addEventListener('click', () => {
    const index = Date.now();
    const row = document.createElement('tr');
    row.innerHTML = `<td><input type="hidden" name="chi_tiets[${index}][nguon]" value="thu_cong"><input class="form-control" name="chi_tiets[${index}][mau]"></td><td><input class="form-control" name="chi_tiets[${index}][size]"></td><td><input type="number" min="0.0001" step="0.0001" class="form-control js-qty" name="chi_tiets[${index}][so_luong]" required value="1"></td><td><input type="text" inputmode="numeric" class="form-control js-money" name="chi_tiets[${index}][thanh_tien]" required></td><td><button type="button" class="btn btn-sm btn-icon btn-outline-danger js-remove"><i class="icon-base bx bx-trash"></i></button></td>`;
    body.appendChild(row); bind(row); totals();
  });
  document.querySelector('form').addEventListener('submit', () => body.querySelectorAll('.js-money').forEach(input => input.value = parseMoney(input.value)));
  totals();
})();
</script>
@endsection
