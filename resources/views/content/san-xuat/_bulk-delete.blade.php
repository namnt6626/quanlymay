<div class="card-body border-top py-3 d-none js-bulk-toolbar">
  <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
    <div class="text-muted">
      Đã chọn <strong class="text-body js-bulk-count">0</strong> {{ $bulkLabel }}
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-danger js-bulk-submit" disabled>
        <i class="icon-base bx bx-trash me-1"></i> Xóa đã chọn
      </button>
      <button type="button" class="btn btn-outline-secondary js-bulk-cancel">Hủy</button>
    </div>
  </div>
</div>

<form action="{{ route($bulkRoute, request()->query()) }}" method="POST" class="d-none js-bulk-form">
  @csrf
  @method('DELETE')
  <div class="js-bulk-hidden-inputs"></div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.querySelector('.js-bulk-toggle');
    const cancelButton = document.querySelector('.js-bulk-cancel');
    const submitButton = document.querySelector('.js-bulk-submit');
    const toolbar = document.querySelector('.js-bulk-toolbar');
    const columns = document.querySelectorAll('.js-bulk-column');
    const checkAll = document.querySelector('.js-bulk-check-all');
    const itemCheckboxes = Array.from(document.querySelectorAll('.js-bulk-item'));
    const countElement = document.querySelector('.js-bulk-count');
    const bulkForm = document.querySelector('.js-bulk-form');
    const hiddenInputs = document.querySelector('.js-bulk-hidden-inputs');

    if (!toggleButton || !toolbar || !bulkForm) {
      return;
    }

    function selectedIds() {
      return [...new Set(itemCheckboxes
        .filter(checkbox => checkbox.checked && !checkbox.disabled)
        .map(checkbox => checkbox.value))];
    }

    function updateState() {
      const ids = selectedIds();
      const selectable = itemCheckboxes.filter(checkbox => !checkbox.disabled);

      countElement.textContent = String(ids.length);
      submitButton.disabled = ids.length === 0;

      if (checkAll) {
        checkAll.checked = selectable.length > 0 && selectable.every(checkbox => checkbox.checked);
        checkAll.indeterminate = selectable.some(checkbox => checkbox.checked) && !checkAll.checked;
      }
    }

    function setBulkMode(enabled) {
      columns.forEach(column => column.classList.toggle('d-none', !enabled));
      toolbar.classList.toggle('d-none', !enabled);
      toggleButton.classList.toggle('d-none', enabled);

      if (!enabled) {
        itemCheckboxes.forEach(checkbox => checkbox.checked = false);
        if (checkAll) checkAll.checked = false;
      }

      updateState();
    }

    toggleButton.addEventListener('click', () => setBulkMode(true));
    cancelButton?.addEventListener('click', () => setBulkMode(false));

    checkAll?.addEventListener('change', function() {
      itemCheckboxes.forEach(checkbox => {
        if (!checkbox.disabled) checkbox.checked = checkAll.checked;
      });
      updateState();
    });

    itemCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        itemCheckboxes
          .filter(item => item.value === checkbox.value && !item.disabled)
          .forEach(item => item.checked = checkbox.checked);
        updateState();
      });
    });

    submitButton?.addEventListener('click', function() {
      const ids = selectedIds();

      if (!ids.length || !window.confirm(`Bạn có chắc muốn xóa ${ids.length} {{ $bulkLabel }} đã chọn?`)) {
        return;
      }

      hiddenInputs.innerHTML = '';
      ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        hiddenInputs.appendChild(input);
      });

      submitButton.disabled = true;
      bulkForm.submit();
    });

    setBulkMode(@json($errors->has('ids')));
  });
</script>
