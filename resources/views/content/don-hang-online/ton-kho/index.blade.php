@extends('layouts/contentNavbarLayout')
@section('title', 'Tồn kho')
@section('page-style')
@include('content.san-xuat._filter-style')
<style>
  .online-stock-scroll {
    overflow: visible;
    scrollbar-gutter: stable;
  }
  .online-stock-table {
    margin-bottom: 0;
  }
  .online-stock-table thead th {
    background: var(--bs-gray-100);
    color: var(--bs-heading-color);
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 3;
  }
  .online-stock-table thead tr.stock-total-row th {
    background: var(--bs-body-bg);
    border-bottom-width: 2px;
    box-shadow: 0 2px 0 rgba(67, 89, 113, .08);
    position: sticky;
    top: 43px;
    z-index: 2;
  }
  .online-stock-table td {
    vertical-align: top;
  }
  .online-product-group-list {
    max-height: 260px;
    overflow-y: auto;
  }
  .online-product-group-item {
    align-items: flex-start;
    border: 1px solid var(--bs-border-color);
    border-radius: .375rem;
    display: flex;
    gap: .75rem;
    padding: .75rem;
  }
  .online-product-group-item .form-check-input {
    flex: 0 0 auto;
    margin: .2rem 0 0;
  }
  .online-product-group-item-name {
    display: block;
    line-height: 1.35;
    min-width: 0;
    overflow-wrap: anywhere;
  }
  .online-product-group-item-name .small {
    line-height: 1.35;
    margin-top: .2rem;
  }
  .online-product-group-editing {
    background: var(--bs-gray-100);
    border: 1px solid var(--bs-border-color);
    border-radius: .375rem;
    display: none;
    padding: .75rem;
  }
  .online-product-group-editing.is-active {
    display: flex;
  }
</style>
@endsection
@section('content')
<div class="card">
  <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <h5 class="mb-0">Tồn kho online</h5>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#productGroupModal">
      <i class="icon-base bx bx-git-merge me-1"></i>Gộp sản phẩm
    </button>
  </div>
  <div class="card-body">
    @if ($errors->any())
      <div class="alert alert-danger">
        {{ $errors->first() }}
      </div>
    @endif
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Sản phẩm</label>
        <input class="form-control" name="ten_san_pham" value="{{ $filters['ten_san_pham'] }}" list="ton-kho-products" placeholder="Gõ hoặc chọn sản phẩm">
        <datalist id="ton-kho-products">@foreach($filterOptions['products'] as $product)<option value="{{ $product }}"></option>@endforeach</datalist>
      </div>
      <div class="col-md-2"><label class="form-label">Màu</label><select class="form-select" name="mau"><option value="">Tất cả</option>@foreach($filterOptions['colors'] as $color)<option value="{{ $color }}" @selected($filters['mau'] === $color)>{{ $color }}</option>@endforeach</select></div>
      <div class="col-md-2"><label class="form-label">Size</label><select class="form-select" name="size"><option value="">Tất cả</option>@foreach($filterOptions['sizes'] as $size)<option value="{{ $size }}" @selected($filters['size'] === $size)>{{ $size }}</option>@endforeach</select></div>
      <div class="col-md-4 d-flex gap-2"><button class="btn btn-primary"><i class="icon-base bx bx-search me-1"></i>Tìm</button><a href="{{ route('ton-kho-online.index') }}" class="btn btn-outline-secondary">Mới</a></div>
    </form>
  </div>
  <div class="online-stock-scroll">
    <table class="table online-stock-table">
      <thead>
        <tr><th>Tên sản phẩm</th><th>Màu</th><th>Size</th><th class="text-end">SL nhập</th><th class="text-end">SL xuất</th><th class="text-end">Tồn</th><th class="text-end">Tiền nhập</th><th class="text-end">Tiền xuất</th><th class="text-end">Tiền xuất - nhập</th></tr>
        <tr class="stock-total-row"><th colspan="3" class="text-end">Tổng</th><th class="text-end">{{ number_format($totals['so_luong_nhap'], 0, ',', '.') }}</th><th class="text-end">{{ number_format($totals['so_luong_xuat'], 0, ',', '.') }}</th><th class="text-end">{{ number_format($totals['so_luong_ton'], 0, ',', '.') }}</th><th class="text-end">{{ number_format($totals['tien_nhap'], 0, ',', '.') }} ₫</th><th class="text-end">{{ number_format($totals['tien_xuat'], 0, ',', '.') }} ₫</th><th class="text-end">{{ number_format($totals['chenh_lech_tien'], 0, ',', '.') }} ₫</th></tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        <tr>
          <td class="fw-semibold">{{ $row['ten_san_pham'] }}</td>
          <td>{{ $row['mau'] ?: '-' }}</td>
          <td>{{ $row['size'] ?: '-' }}</td>
          <td class="text-end">{{ number_format($row['so_luong_nhap'], 0, ',', '.') }}</td>
          <td class="text-end">{{ number_format($row['so_luong_xuat'], 0, ',', '.') }}</td>
          <td class="text-end fw-semibold {{ $row['so_luong_ton'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['so_luong_ton'], 0, ',', '.') }}</td>
          <td class="text-end">{{ number_format($row['tien_nhap'], 0, ',', '.') }} ₫</td>
          <td class="text-end">{{ number_format($row['tien_xuat'], 0, ',', '.') }} ₫</td>
          <td class="text-end fw-semibold {{ $row['chenh_lech_tien'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['chenh_lech_tien'], 0, ',', '.') }} ₫</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center py-4">Chưa có dữ liệu.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="productGroupModal" tabindex="-1" aria-labelledby="productGroupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productGroupModalLabel">Gộp sản phẩm trong thống kê</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">
        <form id="store-product-group-form" method="POST" action="{{ route('ton-kho-online.product-groups.store', request()->query()) }}">
          @csrf
          <input type="hidden" id="online_product_editing_group_name" name="editing_group_name" value="">
          <div class="online-product-group-editing align-items-center justify-content-between gap-2 mb-3" id="online_product_group_editing_banner">
            <div>
              <div class="fw-semibold">Đang sửa nhóm</div>
              <div class="text-muted small" id="online_product_group_editing_text"></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="online_product_group_cancel_edit">Hủy sửa</button>
          </div>
          <div class="mb-3">
            <label class="form-label" for="online_product_group_name">Tên chung <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="online_product_group_name" name="group_name" value="{{ old('group_name') }}" required placeholder="Ví dụ: Áo polo basic">
          </div>
          <div>
            <label class="form-label">Chọn sản phẩm cần gộp <span class="text-danger">*</span></label>
            <div class="online-product-group-list d-flex flex-column gap-2">
              @forelse ($filterOptions['sourceProducts'] as $product)
                @php
                  $checkedProducts = collect(old('products', []));
                  $currentGroup = $productGroups->first(fn ($items) => $items->contains('original_name', $product));
                  $currentGroupName = $currentGroup ? $currentGroup->first()->group_name : '';
                @endphp
                <label class="online-product-group-item mb-0 {{ $currentGroupName !== '' ? 'd-none' : '' }}" data-product-group-item data-current-group="{{ $currentGroupName }}">
                  <input class="form-check-input" type="checkbox" name="products[]" value="{{ $product }}" data-product-checkbox @checked($checkedProducts->contains($product))>
                  <span class="online-product-group-item-name">
                    <span class="fw-semibold">{{ $product }}</span>
                    @if ($currentGroup)
                      <span class="text-muted small d-block">Đang thuộc nhóm: {{ $currentGroup->first()->group_name }}</span>
                    @endif
                  </span>
                </label>
              @empty
                <div class="text-center text-muted py-4">Chưa có sản phẩm để gộp.</div>
              @endforelse
            </div>
          </div>
        </form>

        @if ($productGroups->isNotEmpty())
          <hr>
          <div class="form-label">Nhóm đang có</div>
          <div class="d-flex flex-column gap-2">
            @foreach ($productGroups as $groupName => $aliases)
              <form method="POST" action="{{ route('ton-kho-online.product-groups.destroy', request()->query()) }}" class="d-flex flex-wrap gap-2 align-items-start justify-content-between border rounded p-2">
                @csrf
                @method('DELETE')
                <input type="hidden" name="group_name" value="{{ $groupName }}">
                <div>
                  <div class="fw-semibold">{{ $groupName }}</div>
                  <div class="text-muted small">{{ $aliases->pluck('original_name')->implode(', ') }}</div>
                </div>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-outline-primary" data-edit-product-group data-group-name="{{ $groupName }}" data-products="{{ $aliases->pluck('original_name')->values()->toJson() }}">
                    <i class="icon-base bx bx-edit me-1"></i>Sửa
                  </button>
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="icon-base bx bx-trash me-1"></i>Bỏ gộp
                  </button>
                </div>
              </form>
            @endforeach
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        <button type="submit" class="btn btn-primary" form="store-product-group-form"><i class="icon-base bx bx-save me-1"></i>Lưu nhóm</button>
      </div>
    </div>
  </div>
</div>
@endsection
@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productGroupModal');
    if (!modal) return;

    const groupNameInput = document.getElementById('online_product_group_name');
    const editingInput = document.getElementById('online_product_editing_group_name');
    const editingBanner = document.getElementById('online_product_group_editing_banner');
    const editingText = document.getElementById('online_product_group_editing_text');
    const cancelEditButton = document.getElementById('online_product_group_cancel_edit');
    const items = Array.from(modal.querySelectorAll('[data-product-group-item]'));
    const checkboxes = Array.from(modal.querySelectorAll('[data-product-checkbox]'));

    function resetForm() {
      editingInput.value = '';
      groupNameInput.value = '';
      editingBanner.classList.remove('is-active');
      editingText.textContent = '';
      checkboxes.forEach((checkbox) => checkbox.checked = false);
      items.forEach((item) => {
        item.classList.toggle('d-none', item.dataset.currentGroup !== '');
      });
    }

    function editGroup(groupName, products) {
      const productSet = new Set(products);
      editingInput.value = groupName;
      groupNameInput.value = groupName;
      editingText.textContent = groupName;
      editingBanner.classList.add('is-active');

      items.forEach((item) => {
        const itemGroup = item.dataset.currentGroup || '';
        item.classList.toggle('d-none', itemGroup !== '' && itemGroup !== groupName);
      });

      checkboxes.forEach((checkbox) => {
        checkbox.checked = productSet.has(checkbox.value);
      });

      groupNameInput.focus();
    }

    modal.querySelectorAll('[data-edit-product-group]').forEach((button) => {
      button.addEventListener('click', function() {
        editGroup(this.dataset.groupName || '', JSON.parse(this.dataset.products || '[]'));
      });
    });

    cancelEditButton?.addEventListener('click', resetForm);
    modal.addEventListener('hidden.bs.modal', resetForm);
  });
</script>
@endsection
