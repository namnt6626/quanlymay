@extends('layouts/contentNavbarLayout')

@section('title', 'Nhập phân tích lãi lỗ')

@section('page-style')
<style>
  .profit-upload-grid {
    display: grid;
    gap: .875rem;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }
  .profit-upload-box {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    padding: 1rem;
    min-height: 176px;
    display: flex;
    flex-direction: column;
    gap: .75rem;
  }
  .profit-upload-title {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
  }
  .profit-upload-step {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    font-weight: 800;
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), .1);
  }
  .profit-upload-box .form-label,
  .profit-section-title {
    font-weight: 800;
    color: var(--bs-heading-color);
  }
  .profit-upload-note {
    color: var(--bs-secondary-color);
    font-size: .88rem;
    margin-top: .15rem;
  }
  .profit-upload-status {
    min-height: 24px;
    font-size: .86rem;
    margin-top: .65rem;
  }
  .profit-upload-status.is-ready {
    color: var(--bs-success);
  }
  .profit-upload-status.is-error {
    color: var(--bs-danger);
  }
  .profit-import-setup {
    background: var(--bs-card-bg, #fff);
    border-bottom: 1px solid var(--bs-border-color);
  }
  .profit-upload-section {
    padding-top: 1.5rem;
  }
  .profit-upload-heading {
    margin-top: .75rem;
  }
  .profit-market-badge {
    font-size: .8rem;
    font-weight: 800;
    letter-spacing: 0;
  }
</style>
@endsection

@section('content')
<div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
  <div>
    <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
      <h4 class="mb-0">Nhập dữ liệu lãi lỗ</h4>
      <span class="badge bg-label-{{ $marketplace === 'shopee' ? 'warning' : 'info' }} profit-market-badge">{{ $marketplaceLabel }}</span>
    </div>
    <div class="text-muted">Chọn shop, tháng phân tích, chi phí QC rồi tải đủ bộ file Excel.</div>
  </div>
  <a href="{{ route('phan-tich-lai-lo.index') }}" class="btn btn-outline-secondary">
    <i class="icon-base bx bx-arrow-back me-1"></i>Quay lại
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<form method="POST" action="{{ route('phan-tich-lai-lo.preview') }}" class="card" id="profit-preview-form">
  @csrf
  <input type="hidden" name="import_token" value="{{ $importToken }}">
  <input type="hidden" name="marketplace" value="{{ $marketplace }}">
  <div class="card-header profit-import-setup">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between mb-3">
      <div>
        <h5 class="mb-1">Thông tin nhập liệu</h5>
        <div class="text-muted">Dữ liệu cùng tháng chỉ thay thế khi trùng cả nền tảng và shop.</div>
      </div>
    </div>
    <div class="row g-3 align-items-start">
      <div class="col-12 col-md-6 col-xl-3">
        <label class="form-label">Tháng phân tích</label>
        <select class="form-select" name="period_month" required>
          @foreach($months as $value => $label)
            <option value="{{ $value }}" @selected(old('period_month', now()->format('Y-m')) === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <label class="form-label">Shop / Gian hàng</label>
        <select class="form-select" name="shop_id" id="profit-shop-select" required>
          @foreach($shops as $shop)
            <option value="{{ $shop->id }}" @selected((string) old('shop_id') === (string) $shop->id)>{{ $shop->name }}</option>
          @endforeach
          <option value="__new__" @selected(old('shop_id') === '__new__')>+ Thêm shop mới</option>
        </select>
      </div>
      <div class="col-12 col-md-6 col-xl-3" id="profit-new-shop-wrap" style="display: none;">
        <label class="form-label">Tên shop mới</label>
        <input type="text" class="form-control" name="new_shop_name" id="profit-new-shop-name" value="{{ old('new_shop_name') }}" maxlength="255">
      </div>
      <div class="col-12 col-md-6 col-xl-3">
        <label class="form-label">Chi phí QC mỗi đơn hàng</label>
        <input type="text" class="form-control js-money-input" name="ad_cost_per_order" inputmode="numeric" value="{{ old('ad_cost_per_order') }}" required>
        <div class="form-text">Nhân với số đơn hoàn tất đã lọc theo quyết toán.</div>
      </div>
    </div>
  </div>

  <div class="card-body profit-upload-section">
    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center mb-4 profit-upload-heading">
      <div>
        <div class="profit-section-title">Bộ file Excel</div>
        <div class="text-muted small">Tải từng file lên xong mới bấm kiểm tra dữ liệu.</div>
      </div>
    </div>
    <div class="profit-upload-grid">
      <div class="profit-upload-box">
        <div class="profit-upload-title">
          <span class="profit-upload-step">1</span>
          <div>
            <label class="form-label mb-0">File giá vốn FOB</label>
            <div class="profit-upload-note">Gợi ý mã FOB và giá vốn cho SKU.</div>
          </div>
        </div>
        <input type="file" class="form-control js-profit-file" data-file-key="fob_file" accept=".xlsx">
        <div class="form-text">Ví dụ: Copy of FOB-MỚI NHẤT.xlsx</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="fob_file">
          @if(isset($uploadedFiles['fob_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['fob_file']['name'] }}</span>@endif
        </div>
      </div>
      <div class="profit-upload-box">
        <div class="profit-upload-title">
          <span class="profit-upload-step">2</span>
          <div>
            <label class="form-label mb-0">File quyết toán {{ $marketplaceLabel }}</label>
            <div class="profit-upload-note">Lấy doanh thu, phí sàn và tiền thực nhận.</div>
          </div>
        </div>
        <input type="file" class="form-control js-profit-file" data-file-key="settlement_file" accept=".xlsx">
        <div class="form-text">{{ $marketplace === 'shopee' ? 'Ví dụ: Quyết toán Vinh Thuý SP.xlsx' : 'Ví dụ: quyết toán 20-6-26.xlsx' }}</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="settlement_file">
          @if(isset($uploadedFiles['settlement_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['settlement_file']['name'] }}</span>@endif
        </div>
      </div>
      <div class="profit-upload-box">
        <div class="profit-upload-title">
          <span class="profit-upload-step">3</span>
          <div>
            <label class="form-label mb-0">File tất cả đơn hàng/SKU</label>
            <div class="profit-upload-note">Lấy SKU, số lượng, hoàn/trả và doanh thu từng mã.</div>
          </div>
        </div>
        <input type="file" class="form-control js-profit-file" data-file-key="order_file" accept=".xlsx" @if($marketplace === 'shopee') multiple @endif>
        <div class="form-text">{{ $marketplace === 'shopee' ? 'Có thể chọn nhiều file, ví dụ: Order.all.20260520_20260610.xlsx và Order.all.20260610_20260630.xlsx' : 'Ví dụ: Tất cả đơn hàng-2026-07-30-08_33.xlsx' }}</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="order_file">
          @if(isset($uploadedFiles['order_file']['files']))
            <span class="is-ready">Đã tải lên {{ count($uploadedFiles['order_file']['files']) }} file: {{ collect($uploadedFiles['order_file']['files'])->pluck('name')->implode(', ') }}</span>
          @elseif(isset($uploadedFiles['order_file']))
            <span class="is-ready">Đã tải lên: {{ $uploadedFiles['order_file']['name'] }}</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ route('phan-tich-lai-lo.index') }}" class="btn btn-outline-secondary">Hủy</a>
    <button class="btn btn-primary" id="profit-preview-button">
      <i class="icon-base bx bx-search-alt me-1"></i>Kiểm tra dữ liệu
    </button>
  </div>
</form>
@endsection

@section('page-script')
<script>
  (() => {
    const token = @json($importToken);
    const marketplace = @json($marketplace);
    const uploadUrl = @json(route('phan-tich-lai-lo.upload-file'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const requiredKeys = ['fob_file', 'settlement_file', 'order_file'];
    const uploaded = new Set(@json(array_keys($uploadedFiles)));
    const uploading = new Set();
    const chunkSize = 1024 * 1024;
    const previewForm = document.getElementById('profit-preview-form');
    const previewButton = document.getElementById('profit-preview-button');
    const shopSelect = document.getElementById('profit-shop-select');
    const newShopWrap = document.getElementById('profit-new-shop-wrap');
    const newShopName = document.getElementById('profit-new-shop-name');

    function formatMoney(value) {
      const digits = String(value || '').replace(/\D/g, '');
      return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    document.querySelectorAll('.js-money-input').forEach((input) => {
      input.value = formatMoney(input.value);
      input.addEventListener('input', () => {
        input.value = formatMoney(input.value);
      });
    });

    function refreshShopInput() {
      const isNew = shopSelect?.value === '__new__';
      if (newShopWrap) {
        newShopWrap.style.display = isNew ? '' : 'none';
      }
      if (newShopName) {
        newShopName.required = !!isNew;
        if (!isNew) {
          newShopName.value = '';
        }
      }
    }
    shopSelect?.addEventListener('change', refreshShopInput);
    refreshShopInput();

    function statusFor(key) {
      return document.querySelector(`.js-profit-file-status[data-file-key="${key}"]`);
    }

    function renderStatus(key, html, state = '') {
      const status = statusFor(key);
      if (!status) return;
      status.classList.remove('is-ready', 'is-error');
      if (state) status.classList.add(state);
      status.innerHTML = html;
    }

    function refreshPreviewButton() {
      const missing = requiredKeys.filter((key) => !uploaded.has(key));
      if (previewButton) {
        previewButton.disabled = missing.length > 0 || uploading.size > 0;
      }
    }

    function uploadIdFor(key) {
      const randomPart = Math.random().toString(36).slice(2);
      return `${key}-${Date.now()}-${randomPart}`.replace(/[^A-Za-z0-9_-]/g, '');
    }

    async function uploadFileInChunks(key, file, resetExisting = false) {
      const uploadId = uploadIdFor(key);
      const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));

      for (let index = 0; index < totalChunks; index += 1) {
        const start = index * chunkSize;
        const end = Math.min(file.size, start + chunkSize);
        const body = new FormData();
        body.append('import_token', token);
        body.append('marketplace', marketplace);
        body.append('file_key', key);
        body.append('upload_id', uploadId);
        body.append('chunk_index', String(index));
        body.append('total_chunks', String(totalChunks));
        body.append('original_name', file.name);
        if (resetExisting && index === 0) {
          body.append('reset_existing', '1');
        }
        body.append('chunk', file.slice(start, end), `${file.name}.part`);

        const percent = Math.round(((index + 1) / totalChunks) * 100);
        renderStatus(key, `Đang tải lên ${percent}% (${index + 1}/${totalChunks})`, '');

        const response = await fetch(uploadUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          body,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(data.message || 'Không tải được file.');
        }

        if (data.complete) {
          return data;
        }
      }

      throw new Error('Upload chưa hoàn tất, vui lòng chọn lại file.');
    }

    document.querySelectorAll('.js-profit-file').forEach((input) => {
      input.addEventListener('change', async () => {
        const key = input.dataset.fileKey;
        const files = Array.from(input.files || []);
        const allowMultiple = marketplace === 'shopee' && key === 'order_file';
        const selectedFiles = allowMultiple ? files : files.slice(0, 1);
        if (selectedFiles.length === 0 || !key) return;
        if (selectedFiles.some((file) => !file.name.toLowerCase().endsWith('.xlsx'))) {
          uploaded.delete(key);
          renderStatus(key, 'Chỉ hỗ trợ file .xlsx.', 'is-error');
          input.value = '';
          return;
        }

        input.disabled = true;
        uploading.add(key);
        if (!allowMultiple) {
          uploaded.delete(key);
        }
        refreshPreviewButton();

        try {
          let lastData = null;
          for (let index = 0; index < selectedFiles.length; index += 1) {
            const file = selectedFiles[index];
            if (allowMultiple) {
              renderStatus(key, `Đang tải file ${index + 1}/${selectedFiles.length}: ${file.name}`, '');
            }
            lastData = await uploadFileInChunks(key, file, allowMultiple && index === 0);
          }
          uploaded.add(key);
          if (allowMultiple) {
            const names = (lastData?.files || []).map((file) => file.name).join(', ');
            renderStatus(key, `Đã tải lên ${lastData?.files?.length || selectedFiles.length} file: ${names}`, 'is-ready');
          } else {
            renderStatus(key, `Đã tải lên: ${lastData?.file?.name || selectedFiles[0].name}`, 'is-ready');
          }
        } catch (error) {
          uploaded.delete(key);
          renderStatus(key, error.message || 'Không tải được file.', 'is-error');
        } finally {
          uploading.delete(key);
          input.disabled = false;
          refreshPreviewButton();
        }
      });
    });

    previewForm?.addEventListener('submit', (event) => {
      const missing = requiredKeys.filter((key) => !uploaded.has(key));
      if (missing.length > 0) {
        event.preventDefault();
        alert('Vui lòng tải lên đủ 3 file bắt buộc trước khi kiểm tra dữ liệu.');
      } else if (uploading.size > 0) {
        event.preventDefault();
        alert('File đang tải lên, vui lòng đợi hoàn tất.');
      } else if (shopSelect?.value === '__new__') {
        const name = newShopName?.value?.trim() || '';
        if (!name) {
          event.preventDefault();
          alert('Vui lòng nhập tên shop mới.');
          return;
        }
        if (!confirm(`Tạo shop mới "${name}" cho ${marketplace === 'shopee' ? 'Shopee' : 'TikTok'}?`)) {
          event.preventDefault();
        }
      }
    });

    refreshPreviewButton();
  })();
</script>
@endsection
