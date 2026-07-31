@php
  $statusOptions = ['Completed' => 'Đã nhận hàng hoàn', 'In Process' => 'Đang xử lý', 'To Process' => 'Chờ xử lý', 'Refund rejected' => 'Hoàn bị từ chối'];
  $conditionOptions = ['ban_lai_duoc' => 'Bán lại được', 'loi_hong' => 'Lỗi/hỏng', 'cho_kiem' => 'Chờ kiểm'];
  $details = old('chi_tiets', isset($returnBatch) ? $returnBatch->chiTiets->map(fn ($item) => [
    'return_order_id' => $item->return_order_id,
    'order_id' => $item->order_id,
    'sku_id' => $item->sku_id,
    'seller_sku' => $item->seller_sku,
    'ten_san_pham' => $item->ten_san_pham,
    'mau' => $item->mau,
    'size' => $item->size,
    'sku_name' => $item->sku_name,
    'so_luong_hoan' => $item->so_luong_hoan,
    'return_type' => $item->return_type,
    'return_status' => $item->return_status,
    'tinh_trang_hang' => $item->tinh_trang_hang,
    'refund_time' => $item->refund_time?->format('Y-m-d\TH:i'),
    'time_requested' => $item->time_requested?->format('Y-m-d\TH:i'),
    'return_reason' => $item->return_reason,
    'tracking_id' => $item->tracking_id,
    'compensation_status' => $item->compensation_status,
    'compensation_amount' => $item->compensation_amount,
    'buyer_note' => $item->buyer_note,
  ])->all() : [[
    'return_order_id' => '', 'order_id' => '', 'sku_id' => '', 'seller_sku' => '', 'ten_san_pham' => '',
    'mau' => '', 'size' => '', 'sku_name' => '', 'so_luong_hoan' => 1, 'return_type' => 'Return and refund',
    'return_status' => 'Completed', 'tinh_trang_hang' => 'ban_lai_duoc', 'refund_time' => now()->format('Y-m-d\TH:i'),
    'time_requested' => '', 'return_reason' => '', 'tracking_id' => '', 'compensation_status' => '',
    'compensation_amount' => '', 'buyer_note' => '',
  ]]);
@endphp

@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Thông tin hàng hoàn</h5></div>
  <div class="card-body row g-3">
    <div class="col-md-3">
      <label class="form-label">Ngày nhận hàng hoàn <span class="text-danger">*</span></label>
      <input type="date" name="ngay_hoan" class="form-control" required value="{{ old('ngay_hoan', isset($returnBatch) ? $returnBatch->ngay_hoan?->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-9">
      <label class="form-label">Ghi chú</label>
      <input class="form-control" name="ghi_chu" value="{{ old('ghi_chu', $returnBatch->ghi_chu ?? '') }}">
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Chi tiết hàng hoàn</h5>
    <button type="button" class="btn btn-outline-primary" id="add-return-row"><i class="icon-base bx bx-plus me-1"></i>Thêm dòng</button>
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0" id="return-detail-table" style="min-width: 1500px">
      <thead>
        <tr>
          <th>Mã đơn gốc</th>
          <th>Mã đơn hoàn</th>
          <th>Seller SKU</th>
          <th>Sản phẩm</th>
          <th>Màu</th>
          <th>Size</th>
          <th class="text-end">SL hoàn</th>
          <th>Trạng thái</th>
          <th>Tình trạng hàng</th>
          <th>Ngày hoàn</th>
          <th>Lý do</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($details as $detail)
          <tr>
            @php $index = $loop->index; @endphp
            <td><input class="form-control" name="chi_tiets[{{ $index }}][order_id]" value="{{ $detail['order_id'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][return_order_id]" value="{{ $detail['return_order_id'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][sku_id]" value="{{ $detail['sku_id'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][return_type]" value="{{ $detail['return_type'] ?? 'Return and refund' }}"><input type="hidden" name="chi_tiets[{{ $index }}][sku_name]" value="{{ $detail['sku_name'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][time_requested]" value="{{ $detail['time_requested'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][tracking_id]" value="{{ $detail['tracking_id'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][compensation_status]" value="{{ $detail['compensation_status'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][compensation_amount]" value="{{ $detail['compensation_amount'] ?? '' }}"><input type="hidden" name="chi_tiets[{{ $index }}][buyer_note]" value="{{ $detail['buyer_note'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][seller_sku]" value="{{ $detail['seller_sku'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][ten_san_pham]" required value="{{ $detail['ten_san_pham'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][mau]" value="{{ $detail['mau'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][size]" value="{{ $detail['size'] ?? '' }}"></td>
            <td><input type="number" min="0.0001" step="0.0001" class="form-control text-end" name="chi_tiets[{{ $index }}][so_luong_hoan]" required value="{{ $detail['so_luong_hoan'] ?? 1 }}"></td>
            <td><select class="form-select" name="chi_tiets[{{ $index }}][return_status]" required>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(($detail['return_status'] ?? 'Completed') === $value)>{{ $label }}</option>@endforeach</select></td>
            <td><select class="form-select" name="chi_tiets[{{ $index }}][tinh_trang_hang]" required>@foreach($conditionOptions as $value => $label)<option value="{{ $value }}" @selected(($detail['tinh_trang_hang'] ?? 'ban_lai_duoc') === $value)>{{ $label }}</option>@endforeach</select></td>
            <td><input type="datetime-local" class="form-control" name="chi_tiets[{{ $index }}][refund_time]" value="{{ $detail['refund_time'] ?? '' }}"></td>
            <td><input class="form-control" name="chi_tiets[{{ $index }}][return_reason]" value="{{ $detail['return_reason'] ?? '' }}"></td>
            <td><button type="button" class="btn btn-sm btn-icon btn-outline-danger js-remove-row"><i class="icon-base bx bx-trash"></i></button></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <div class="text-muted">Chỉ dòng Completed + Return and refund + Bán lại được mới cộng tồn.</div>
    <div class="d-flex gap-2">
      <a href="{{ route('hang-hoan-online.index') }}" class="btn btn-outline-secondary">Hủy</a>
      <button class="btn btn-primary">Lưu</button>
    </div>
  </div>
</div>

@section('page-script')
@parent
<script>
(() => {
  const body = document.querySelector('#return-detail-table tbody');
  const template = index => `<tr><td><input class="form-control" name="chi_tiets[${index}][order_id]"></td><td><input class="form-control" name="chi_tiets[${index}][return_order_id]"><input type="hidden" name="chi_tiets[${index}][sku_id]"><input type="hidden" name="chi_tiets[${index}][return_type]" value="Return and refund"><input type="hidden" name="chi_tiets[${index}][sku_name]"><input type="hidden" name="chi_tiets[${index}][time_requested]"><input type="hidden" name="chi_tiets[${index}][tracking_id]"><input type="hidden" name="chi_tiets[${index}][compensation_status]"><input type="hidden" name="chi_tiets[${index}][compensation_amount]"><input type="hidden" name="chi_tiets[${index}][buyer_note]"></td><td><input class="form-control" name="chi_tiets[${index}][seller_sku]"></td><td><input class="form-control" name="chi_tiets[${index}][ten_san_pham]" required></td><td><input class="form-control" name="chi_tiets[${index}][mau]"></td><td><input class="form-control" name="chi_tiets[${index}][size]"></td><td><input type="number" min="0.0001" step="0.0001" class="form-control text-end" name="chi_tiets[${index}][so_luong_hoan]" required value="1"></td><td><select class="form-select" name="chi_tiets[${index}][return_status]" required><option value="Completed">Đã nhận hàng hoàn</option><option value="In Process">Đang xử lý</option><option value="To Process">Chờ xử lý</option><option value="Refund rejected">Hoàn bị từ chối</option></select></td><td><select class="form-select" name="chi_tiets[${index}][tinh_trang_hang]" required><option value="ban_lai_duoc">Bán lại được</option><option value="loi_hong">Lỗi/hỏng</option><option value="cho_kiem">Chờ kiểm</option></select></td><td><input type="datetime-local" class="form-control" name="chi_tiets[${index}][refund_time]"></td><td><input class="form-control" name="chi_tiets[${index}][return_reason]"></td><td><button type="button" class="btn btn-sm btn-icon btn-outline-danger js-remove-row"><i class="icon-base bx bx-trash"></i></button></td></tr>`;
  const bind = row => row.querySelector('.js-remove-row')?.addEventListener('click', () => {
    if (body.querySelectorAll('tr').length > 1) row.remove();
  });
  body.querySelectorAll('tr').forEach(bind);
  document.querySelector('#add-return-row')?.addEventListener('click', () => {
    const tpl = document.createElement('template');
    tpl.innerHTML = template(Date.now());
    const row = tpl.content.firstElementChild;
    body.appendChild(row);
    bind(row);
  });
})();
</script>
@endsection
