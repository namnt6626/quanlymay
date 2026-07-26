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

<form method="POST" action="{{ route('phan-tich-lai-lo.preview') }}" enctype="multipart/form-data" class="card">
  @csrf
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
        <input type="file" class="form-control" name="fob_file" accept=".xlsx">
        <div class="form-text">Ví dụ: Copy of FOB-MỚI NHẤT.xlsx</div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">1. File số liệu phân tích</label>
        <div class="profit-upload-note">Lấy GMV, số món bán, đơn hàng, khách hàng, traffic.</div>
        <input type="file" class="form-control" name="analytics_file" accept=".xlsx" required>
        <div class="form-text">Ví dụ: SỐ LIỆU PHÂN TÍCH.xlsx</div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">2. File chi phí QC</label>
        <div class="profit-upload-note">Lấy tổng tiền quảng cáo, CPA, doanh thu gộp, ROI.</div>
        <input type="file" class="form-control" name="ad_file" accept=".xlsx" required>
        <div class="form-text">Ví dụ: CHI PHÍ QC.xlsx</div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">3. File quyết toán TikTok</label>
        <div class="profit-upload-note">Lấy doanh thu quyết toán, phí sàn, phí giao dịch, tiền thực nhận.</div>
        <input type="file" class="form-control" name="settlement_file" accept=".xlsx" required>
        <div class="form-text">Ví dụ: 16-306.xlsx</div>
      </div>
      <div class="profit-upload-box">
        <label class="form-label">4. File tất cả đơn hàng/SKU</label>
        <div class="profit-upload-note">Lấy Seller SKU, số lượng bán, hoàn/trả, doanh thu từng SKU.</div>
        <input type="file" class="form-control" name="order_file" accept=".xlsx" required>
        <div class="form-text">Ví dụ: Tất cả đơn hàng-2026-07-26-15_58.xlsx</div>
      </div>
    </div>
  </div>

  <div class="card-footer d-flex justify-content-end gap-2">
    <a href="{{ route('phan-tich-lai-lo.index') }}" class="btn btn-outline-secondary">Hủy</a>
    <button class="btn btn-primary">
      <i class="icon-base bx bx-search-alt me-1"></i>Kiểm tra dữ liệu
    </button>
  </div>
</form>
@endsection
