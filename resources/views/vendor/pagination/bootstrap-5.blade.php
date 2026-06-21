@if ($paginator->hasPages())
  <nav class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3" aria-label="Phân trang">
    <div class="d-flex d-sm-none align-items-center justify-content-between w-100 gap-2">
      @if ($paginator->onFirstPage())
        <span class="btn btn-sm btn-outline-secondary disabled flex-fill" aria-disabled="true">
          <i class="icon-base bx bx-chevron-left"></i>
          Trước
        </span>
      @else
        <a class="btn btn-sm btn-outline-primary flex-fill" href="{{ $paginator->previousPageUrl() }}" rel="prev">
          <i class="icon-base bx bx-chevron-left"></i>
          Trước
        </a>
      @endif

      <span class="text-muted small text-nowrap">
        Trang {{ $paginator->currentPage() }}/{{ $paginator->lastPage() }}
      </span>

      @if ($paginator->hasMorePages())
        <a class="btn btn-sm btn-outline-primary flex-fill" href="{{ $paginator->nextPageUrl() }}" rel="next">
          Sau
          <i class="icon-base bx bx-chevron-right"></i>
        </a>
      @else
        <span class="btn btn-sm btn-outline-secondary disabled flex-fill" aria-disabled="true">
          Sau
          <i class="icon-base bx bx-chevron-right"></i>
        </span>
      @endif
    </div>

    <div class="d-none d-sm-flex flex-sm-fill align-items-sm-center justify-content-sm-between gap-3 w-100">
      <p class="small text-muted mb-0">
        Hiển thị
        <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
        đến
        <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
        trong tổng số
        <span class="fw-semibold">{{ $paginator->total() }}</span>
        kết quả
      </p>

      <ul class="pagination mb-0">
        @if ($paginator->onFirstPage())
          <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
            <span class="page-link" aria-hidden="true">&lsaquo;</span>
          </li>
        @else
          <li class="page-item">
            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
              aria-label="@lang('pagination.previous')">&lsaquo;</a>
          </li>
        @endif

        @foreach ($elements as $element)
          @if (is_string($element))
            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
          @endif

          @if (is_array($element))
            @foreach ($element as $page => $url)
              @if ($page == $paginator->currentPage())
                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
              @else
                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
              @endif
            @endforeach
          @endif
        @endforeach

        @if ($paginator->hasMorePages())
          <li class="page-item">
            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
              aria-label="@lang('pagination.next')">&rsaquo;</a>
          </li>
        @else
          <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
            <span class="page-link" aria-hidden="true">&rsaquo;</span>
          </li>
        @endif
      </ul>
    </div>
  </nav>
@endif
