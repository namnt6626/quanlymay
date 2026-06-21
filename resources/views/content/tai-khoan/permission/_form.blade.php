@php
  $currentPermission = $permission ?? null;
@endphp

<div class="row g-4">
  <div class="col-md-4">
    <label class="form-label" for="ma_quyen">Mã quyền <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('ma_quyen') is-invalid @enderror" id="ma_quyen" name="ma_quyen"
      value="{{ old('ma_quyen', $currentPermission?->ma_quyen ?? '') }}" maxlength="100" required>
    @error('ma_quyen')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-8">
    <label class="form-label" for="ten_quyen">Tên quyền <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('ten_quyen') is-invalid @enderror" id="ten_quyen" name="ten_quyen"
      value="{{ old('ten_quyen', $currentPermission?->ten_quyen ?? '') }}" maxlength="255" required>
    @error('ten_quyen')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="module">Module <span class="text-danger">*</span></label>
    <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required>
      <option value="">-- Chọn module --</option>
      @foreach ($moduleOptions as $value => $label)
        <option value="{{ $value }}" @selected(old('module', $currentPermission?->module ?? '') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('module')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="action">Hành động <span class="text-danger">*</span></label>
    <select class="form-select @error('action') is-invalid @enderror" id="action" name="action" required>
      <option value="">-- Chọn hành động --</option>
      @foreach ($actionOptions as $value => $label)
        <option value="{{ $value }}" @selected(old('action', $currentPermission?->action ?? '') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('action')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label" for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
    <select class="form-select @error('trang_thai') is-invalid @enderror" id="trang_thai" name="trang_thai" required>
      <option value="1" @selected((string) old('trang_thai', (int) ($currentPermission?->trang_thai ?? 1)) === '1')>Hoạt động</option>
      <option value="0" @selected((string) old('trang_thai', (int) ($currentPermission?->trang_thai ?? 1)) === '0')>Ngừng hoạt động</option>
    </select>
    @error('trang_thai')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <label class="form-label" for="mo_ta">Mô tả</label>
    <textarea class="form-control @error('mo_ta') is-invalid @enderror" id="mo_ta" name="mo_ta" rows="4">{{ old('mo_ta', $currentPermission?->mo_ta ?? '') }}</textarea>
    @error('mo_ta')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-12">
    <div class="d-flex gap-2 flex-wrap">
      <button type="submit" class="btn btn-primary">
        <i class="icon-base bx bx-save me-1"></i> Lưu
      </button>
      <a href="{{ route('permission.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </div>
  </div>
</div>
