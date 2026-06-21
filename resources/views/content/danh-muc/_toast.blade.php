@php
    $toastType = null;
    $toastTitle = null;
    $toastMessage = null;

    if (session('success')) {
        $toastType = 'success';
        $toastTitle = 'Thành công';
        $toastMessage = session('success');
    } elseif (session('error')) {
        $toastType = 'danger';
        $toastTitle = 'Có lỗi xảy ra';
        $toastMessage = session('error');
    } elseif ($errors->any()) {
        $toastType = 'danger';
        $toastTitle = 'Dữ liệu chưa hợp lệ';
        $toastMessage = 'Vui lòng kiểm tra lại các trường thông tin.';
    }
@endphp

@if ($toastType)
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
        <div
            class="toast js-auto-toast text-bg-{{ $toastType }} border-0"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            data-bs-delay="3000"
            data-bs-autohide="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>{{ $toastTitle }}</strong>
                    <div>{{ $toastMessage }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endif

@once
    @section('page-script')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.js-auto-toast').forEach(function (toastElement) {
                    bootstrap.Toast.getOrCreateInstance(toastElement, {
                        autohide: true,
                        delay: 3000
                    }).show();
                });
            });
        </script>
    @endsection
@endonce
