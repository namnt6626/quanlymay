@php
  $currentUser = $user ?? null;
  $isEditMode = $currentUser !== null;
  $isCurrentUser = $isCurrentUser ?? false;
@endphp

<div class="row g-4">
  <div class="col-md-6">
    <label class="form-label" for="name">Họ tên <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
      value="{{ old('name', $currentUser?->name ?? '') }}" maxlength="255" required>
    @error('name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="username">Tên đăng nhập <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username"
      value="{{ old('username', $currentUser?->username ?? '') }}" maxlength="100" required>
    @error('username')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="email">Email</label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
      value="{{ old('email', $currentUser?->email ?? '') }}" maxlength="255">
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="phone">Số điện thoại</label>
    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
      value="{{ old('phone', $currentUser?->phone ?? '') }}" placeholder="VD: 0912345678">
    @error('phone')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="role_id">Vai trò <span class="text-danger">*</span></label>
    <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
      <option value="">-- Chọn vai trò --</option>
      @foreach ($roles as $roleOption)
        <option value="{{ $roleOption->id }}" @selected((string) old('role_id', (string) ($currentUser?->role_id ?? '')) === (string) $roleOption->id)>
          {{ $roleOption->ma_vai_tro }} - {{ $roleOption->ten_vai_tro }}
        </option>
      @endforeach
    </select>
    @error('role_id')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="status">Trạng thái <span class="text-danger">*</span></label>
    @if ($isEditMode && $isCurrentUser)
      <input type="hidden" name="status" value="{{ old('status', (int) $currentUser?->status) }}">
      <select class="form-select" id="status" disabled>
        <option value="1" @selected((string) old('status', (int) $currentUser?->status) === '1')>Hoạt động</option>
        <option value="0" @selected((string) old('status', (int) $currentUser?->status) === '0')>Khóa</option>
      </select>
      <div class="form-text text-warning">Không thể khóa tài khoản đang đăng nhập.</div>
    @else
      <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
        <option value="1" @selected((string) old('status', (int) ($currentUser?->status ?? 1)) === '1')>Hoạt động</option>
        <option value="0" @selected((string) old('status', (int) ($currentUser?->status ?? 1)) === '0')>Khóa</option>
      </select>
      @error('status')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    @endif
  </div>

  <div class="col-md-6">
    <label class="form-label" for="password">
      {{ $isEditMode ? 'Mật khẩu mới' : 'Mật khẩu' }}
      @unless ($isEditMode)
        <span class="text-danger">*</span>
      @endunless
    </label>
    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
      {{ $isEditMode ? '' : 'required' }} minlength="8"
      placeholder="{{ $isEditMode ? 'Để trống nếu không đổi mật khẩu' : '' }}">
    @error('password')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label" for="password_confirmation">
      {{ $isEditMode ? 'Xác nhận mật khẩu mới' : 'Xác nhận mật khẩu' }}
      @unless ($isEditMode)
        <span class="text-danger">*</span>
      @endunless
    </label>
    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
      {{ $isEditMode ? '' : 'required' }} minlength="8"
      placeholder="{{ $isEditMode ? 'Để trống nếu không đổi mật khẩu' : '' }}">
  </div>

  <div class="col-12">
    <div class="d-flex gap-2 flex-wrap">
      <button type="submit" class="btn btn-primary">
        <i class="icon-base bx bx-save me-1"></i> Lưu
      </button>
      <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </div>
  </div>
</div>
