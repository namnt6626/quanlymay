@extends('layouts/contentNavbarLayout')

@section('title', 'Nhập phân tích lãi lỗ')

@section('page-style')
<style>
  .profit-upload-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  }
  .profit-upload-box {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    padding: 1rem;
    min-height: 160px;
  }
  .profit-upload-box .form-label {
    font-weight: 800;
    color: var(--bs-heading-color);
  }
  .profit-upload-note {
    min-height: 44px;
    color: var(--bs-secondary-color);
    font-size: .88rem;
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
</style>
@endsection

@section('content')
<div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center mb-4">
  <div>
    <h4 class="mb-1">Nhập dữ liệu phân tích lãi lỗ</h4>
    <div class="text-muted">Chọn tháng trước, upload 4 file bắt buộc; FOB chỉ dùng để gợi ý giá vốn nếu có.</div>
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
  <div class="card-header">
    <div class="row g-3 align-items-end">
      <div class="col-12 col-md-4 col-xl-3">
        <label class="form-label">Tháng phân tích</label>
        <select class="form-select" name="period_month" required>
          @foreach($months as $value => $label)
            <option value="{{ $value }}" @selected(old('period_month', now()->subMonth()->format('Y-m')) === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-8 col-xl-9 text-muted">
        Lần upload mới sẽ cần xác nhận lại giá vốn; dữ liệu tháng cũ chỉ bị thay thế sau khi bấm xác nhận cập nhật.
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="profit-upload-grid">
      <div class="profit-upload-box">
        <label class="form-label">File giá vốn FOB <span class="text-muted fw-normal">(tùy chọn)</span></label>
        <div class="profit-upload-note">Dùng để tự gợi ý mã FOB và giá vốn cho SKU chưa lưu.</div>
        <input type="file" class="form-control js-profit-file" data-file-key="fob_file" accept=".xlsx">
        <div class="form-text">Ví dụ: Copy of FOB-MỚI NHẤT.xlsx</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="fob_file">
          @if(isset($uploadedFiles['fob_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['fob_file']['name'] }}</span>@endif
        </div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">1. File số liệu phân tích</label>
        <div class="profit-upload-note">Lấy GMV, số món bán, đơn hàng, khách hàng, traffic.</div>
        <input type="file" class="form-control js-profit-file" data-file-key="analytics_file" accept=".xlsx">
        <div class="form-text">Ví dụ: SỐ LIỆU PHÂN TÍCH.xlsx</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="analytics_file">
          @if(isset($uploadedFiles['analytics_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['analytics_file']['name'] }}</span>@endif
        </div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">2. File chi phí QC</label>
        <div class="profit-upload-note">Lấy tổng tiền quảng cáo, CPA, doanh thu gộp, ROI.</div>
        <input type="file" class="form-control js-profit-file" data-file-key="ad_file" accept=".xlsx">
        <div class="form-text">Ví dụ: CHI PHÍ QC.xlsx</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="ad_file">
          @if(isset($uploadedFiles['ad_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['ad_file']['name'] }}</span>@endif
        </div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">3. File quyết toán TikTok</label>
        <div class="profit-upload-note">Lấy doanh thu quyết toán, phí sàn, phí giao dịch, tiền thực nhận.</div>
        <input type="file" class="form-control js-profit-file" data-file-key="settlement_file" accept=".xlsx">
        <div class="form-text">Ví dụ: 16-306.xlsx</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="settlement_file">
          @if(isset($uploadedFiles['settlement_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['settlement_file']['name'] }}</span>@endif
        </div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">4. File tất cả đơn hàng/SKU</label>
        <div class="profit-upload-note">Lấy Seller SKU, số lượng bán, hoàn/trả, doanh thu từng SKU.</div>
        <input type="file" class="form-control js-profit-file" data-file-key="order_file" accept=".xlsx">
        <div class="form-text">Ví dụ: Tất cả đơn hàng-2026-07-26-15_58.xlsx</div>
        <div class="profit-upload-status js-profit-file-status" data-file-key="order_file">
          @if(isset($uploadedFiles['order_file']))<span class="is-ready">Đã tải lên: {{ $uploadedFiles['order_file']['name'] }}</span>@endif
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
    const uploadUrl = @json(route('phan-tich-lai-lo.upload-file'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const requiredKeys = ['analytics_file', 'ad_file', 'settlement_file', 'order_file'];
    const uploaded = new Set(@json(array_keys($uploadedFiles)));
    const uploading = new Set();
    const chunkSize = 1024 * 1024;
    const previewForm = document.getElementById('profit-preview-form');
    const previewButton = document.getElementById('profit-preview-button');

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

    async function uploadFileInChunks(key, file) {
      const uploadId = uploadIdFor(key);
      const totalChunks = Math.max(1, Math.ceil(file.size / chunkSize));

      for (let index = 0; index < totalChunks; index += 1) {
        const start = index * chunkSize;
        const end = Math.min(file.size, start + chunkSize);
        const body = new FormData();
        body.append('import_token', token);
        body.append('file_key', key);
        body.append('upload_id', uploadId);
        body.append('chunk_index', String(index));
        body.append('total_chunks', String(totalChunks));
        body.append('original_name', file.name);
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
        const file = input.files?.[0];
        const key = input.dataset.fileKey;
        if (!file || !key) return;
        if (!file.name.toLowerCase().endsWith('.xlsx')) {
          uploaded.delete(key);
          renderStatus(key, 'Chỉ hỗ trợ file .xlsx.', 'is-error');
          input.value = '';
          return;
        }

        input.disabled = true;
        uploaded.delete(key);
        uploading.add(key);
        refreshPreviewButton();

        try {
          const data = await uploadFileInChunks(key, file);
          uploaded.add(key);
          renderStatus(key, `Đã tải lên: ${data.file?.name || file.name}`, 'is-ready');
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
        alert('Vui lòng tải lên đủ 4 file bắt buộc trước khi kiểm tra dữ liệu.');
      } else if (uploading.size > 0) {
        event.preventDefault();
        alert('File đang tải lên, vui lòng đợi hoàn tất.');
      }
    });

    refreshPreviewButton();
  })();
</script>
@endsection
