@extends('layouts/contentNavbarLayout')

@section('title', 'Chi tiết nhật ký thao tác')

@section('content')
  @php
    $prettyJson = function ($value): string {
        if ($value === null || $value === []) {
            return '-';
        }

        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    };
  @endphp

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center gap-3">
      <h5 class="mb-0">Chi tiết nhật ký thao tác</h5>
      <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary">
        <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
      </a>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="text-muted small">Thời gian</div>
          <div class="fw-semibold">{{ $activityLog->created_at?->format('d/m/Y H:i:s') }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Người thao tác</div>
          <div class="fw-semibold">{{ $activityLog->user_name ?: '-' }}</div>
        </div>
        <div class="col-md-2">
          <div class="text-muted small">Module</div>
          <div class="fw-semibold">{{ $activityLog->module }}</div>
        </div>
        <div class="col-md-2">
          <div class="text-muted small">Hành động</div>
          <div class="fw-semibold">{{ $activityLog->action }}</div>
        </div>
        <div class="col-md-2">
          <div class="text-muted small">Batch ID</div>
          <div class="fw-semibold text-break">{{ $activityLog->batch_id ?: '-' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">IP</div>
          <div class="fw-semibold">{{ $activityLog->ip_address ?: '-' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Route</div>
          <div class="fw-semibold">{{ $activityLog->route_name ?: '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">URL</div>
          <div class="fw-semibold text-break">{{ $activityLog->url ?: '-' }}</div>
        </div>
        <div class="col-12">
          <div class="text-muted small">Mô tả</div>
          <div class="fw-semibold">{{ $activityLog->description ?: '-' }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="mb-0">Dữ liệu cũ</h6>
        </div>
        <div class="card-body">
          <pre class="mb-0 bg-light rounded p-3 text-wrap" style="white-space: pre-wrap;">{{ $prettyJson($activityLog->old_values) }}</pre>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h6 class="mb-0">Dữ liệu mới</h6>
        </div>
        <div class="card-body">
          <pre class="mb-0 bg-light rounded p-3 text-wrap" style="white-space: pre-wrap;">{{ $prettyJson($activityLog->new_values) }}</pre>
        </div>
      </div>
    </div>
  </div>
@endsection
