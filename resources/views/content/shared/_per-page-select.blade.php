@php
  $paginationPerPageValue = paginationPerPage();
@endphp

<div class="col-6 col-md-3 col-xl-auto">
  <label class="form-label" for="per_page">Hiển thị</label>
  <select class="form-select" id="per_page" name="per_page" onchange="this.form.submit()">
    @foreach (paginationPerPageOptions() as $option)
      <option value="{{ $option }}" @selected($paginationPerPageValue === $option)>{{ $option }} dòng</option>
    @endforeach
  </select>
</div>
