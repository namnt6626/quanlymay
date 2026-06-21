@extends('layouts/contentNavbarLayout')

@section('title', 'Người dùng')

@section('content')
  @include('content.danh-muc._toast')

  <div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
      <h5 class="mb-0">Danh sách tài khoản</h5>
      @if (hasPermission('USER_CREATE'))
        <a href="{{ route('user.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus me-1"></i> Thêm mới
        </a>
      @endif
    </div>

    <div class="card-body">
      <form action="{{ route('user.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-xl">
          <label class="form-label" for="q">Tìm kiếm</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $keyword }}"
            placeholder="Nhập họ tên, tên đăng nhập, email, số điện thoại hoặc vai trò">
        </div>
        <div class="col-12 col-xl-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('user.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>

    <div class="table-responsive text-nowrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 80px;">STT</th>
            <th>Họ tên</th>
            <th>Tên đăng nhập</th>
            <th>Email</th>
            <th>Số điện thoại</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th>Lần đăng nhập cuối</th>
            <th style="width: 140px;">Thao tác</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($users as $user)
            <tr>
              <td>{{ $users->firstItem() + $loop->index }}</td>
              <td><strong>{{ $user->name }}</strong></td>
              <td>{{ $user->username ?: '-' }}</td>
              <td>{{ $user->email ?: '-' }}</td>
              <td>{{ $user->phone ?: '-' }}</td>
              <td>{{ $user->role?->ten_vai_tro ? $user->role->ma_vai_tro . ' - ' . $user->role->ten_vai_tro : '-' }}</td>
              <td>
                @if ($user->status)
                  <span class="badge bg-label-success">Hoạt động</span>
                @else
                  <span class="badge bg-label-secondary">Khóa</span>
                @endif
              </td>
              <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?: '-' }}</td>
              <td>
                <div class="d-flex gap-2">
                  @if (hasPermission('USER_EDIT'))
                    <a href="{{ route('user.edit', $user) }}" class="btn btn-sm btn-icon btn-outline-primary"
                      title="Sửa">
                      <i class="icon-base bx bx-edit"></i>
                    </a>
                  @endif
                  @if (hasPermission('USER_DELETE') && auth()->id() !== $user->id)
                    <form action="{{ route('user.destroy', $user) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Xóa">
                        <i class="icon-base bx bx-trash"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-4">Chưa có dữ liệu người dùng.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($users->hasPages())
      <div class="card-footer">
        {{ $users->links() }}
      </div>
    @endif
  </div>
@endsection
