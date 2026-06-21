@extends('layouts/contentNavbarLayout')

@section('title', 'Nhật ký thao tác')

@section('content')
  @php
    $actionLabels = [
        'LOGIN' => 'Đăng nhập',
        'LOGOUT' => 'Đăng xuất',
        'CREATE' => 'Thêm mới',
        'UPDATE' => 'Cập nhật',
        'DELETE' => 'Xóa',
        'RESTORE' => 'Khôi phục',
        'RESET_PASSWORD' => 'Đặt lại mật khẩu',
        'CHANGE_STATUS' => 'Đổi trạng thái',
        'IMPORT' => 'Import',
        'EXPORT' => 'Export',
        'AUTO_IMPORT_FROM_QC' => 'Tự nhập kho từ QC',
        'SYNC_QC_TO_KHO' => 'Sync QC sang kho',
        'BULK_EXPORT_KHO' => 'Xuất kho nhiều dòng',
        'ASSIGN_PERMISSION' => 'Gán quyền',
    ];
  @endphp

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Nhật ký thao tác người dùng</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('activity-logs.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-6 col-lg-2">
          <label class="form-label" for="date_from">Từ ngày</label>
          <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
        </div>
        <div class="col-6 col-lg-2">
          <label class="form-label" for="date_to">Đến ngày</label>
          <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
        </div>
        <div class="col-12 col-lg-2">
          <label class="form-label" for="user_id">Người thao tác</label>
          <select class="form-select" id="user_id" name="user_id">
            <option value="">Tất cả</option>
            @foreach ($users as $user)
              <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>
                {{ $user->name ?: $user->username }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-lg-2">
          <label class="form-label" for="module">Module</label>
          <select class="form-select" id="module" name="module">
            <option value="">Tất cả</option>
            @foreach ($modules as $module)
              <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-lg-2">
          <label class="form-label" for="action">Hành động</label>
          <select class="form-select" id="action" name="action">
            <option value="">Tất cả</option>
            @foreach ($actions as $action)
              <option value="{{ $action }}" @selected($filters['action'] === $action)>
                {{ $actionLabels[$action] ?? $action }}
              </option>
            @endforeach
          </select>
        </div>
        @include('content.shared._per-page-select')
        <div class="col-12 col-lg">
          <label class="form-label" for="q">Từ khóa</label>
          <input type="text" class="form-control" id="q" name="q" value="{{ $filters['q'] }}"
            placeholder="Nhập mô tả, IP, route hoặc URL">
        </div>
        <div class="col-12 col-lg-auto">
          <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-primary">
              <i class="icon-base bx bx-search me-1"></i> Tìm kiếm
            </button>
            <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary">Làm mới</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="min-width: 150px;">Thời gian</th>
            <th style="min-width: 150px;">Người thao tác</th>
            <th>Module</th>
            <th>Hành động</th>
            <th style="min-width: 280px;">Mô tả</th>
            <th>IP</th>
            <th style="width: 90px;">Chi tiết</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @forelse ($activityLogs as $activityLog)
            <tr>
              <td>{{ $activityLog->created_at?->format('d/m/Y H:i:s') }}</td>
              <td>{{ $activityLog->user_name ?: '-' }}</td>
              <td><span class="badge bg-label-primary">{{ $activityLog->module }}</span></td>
              <td>{{ $actionLabels[$activityLog->action] ?? $activityLog->action }}</td>
              <td class="text-wrap">{{ $activityLog->description ?: '-' }}</td>
              <td>{{ $activityLog->ip_address ?: '-' }}</td>
              <td>
                <a href="{{ route('activity-logs.show', $activityLog) }}" class="btn btn-sm btn-icon btn-outline-info"
                  title="Chi tiết">
                  <i class="icon-base bx bx-detail"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4">Chưa có nhật ký thao tác.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($activityLogs->hasPages())
      <div class="card-footer">
        {{ $activityLogs->links() }}
      </div>
    @endif
  </div>
@endsection
