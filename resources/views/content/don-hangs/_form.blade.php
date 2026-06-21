@php
  $rows = old('chi_tiets', $detailRows ?? []);

  if (!is_array($rows) || $rows === []) {
      $rows = [
          [
              'mat_hang_id' => '',
              'items' => [
                  [
                      'mau_id' => '',
                      'size_id' => '',
                      'so_luong_dat' => '',
                      'ghi_chu' => '',
                  ],
              ],
          ],
      ];
  }

  $groups = [];

  foreach ($rows as $rowIndex => $row) {
      if (!is_array($row)) {
          continue;
      }

      if (array_key_exists('items', $row) && is_array($row['items'])) {
          $items = $row['items'];

          if ($items === []) {
              $items = [
                  [
                      'mau_id' => '',
                      'size_id' => '',
                      'so_luong_dat' => '',
                      'ghi_chu' => '',
                  ],
              ];
          }

          $groups[] = [
              'mat_hang_id' => $row['mat_hang_id'] ?? '',
              'items' => array_values($items),
              '_error_index' => is_numeric($rowIndex) ? (int) $rowIndex : null,
          ];

          continue;
      }

      $matHangKey = (string) ($row['mat_hang_id'] ?? '');
      $groupKey = $matHangKey === '' ? '__empty_' . $rowIndex : $matHangKey;

      if (!isset($groups[$groupKey])) {
          $groups[$groupKey] = [
              'mat_hang_id' => $row['mat_hang_id'] ?? '',
              'items' => [],
              '_error_index' => is_numeric($rowIndex) ? (int) $rowIndex : null,
          ];
      }

      $groups[$groupKey]['items'][] = [
          'mau_id' => $row['mau_id'] ?? '',
          'size_id' => $row['size_id'] ?? '',
          'so_luong_dat' => $row['so_luong_dat'] ?? '',
          'ghi_chu' => $row['ghi_chu'] ?? '',
          '_error_index' => is_numeric($rowIndex) ? (int) $rowIndex : null,
      ];
  }

  $groups = array_values($groups);

  if ($groups === []) {
      $groups = [
          [
              'mat_hang_id' => '',
              'items' => [
                  [
                      'mau_id' => '',
                      'size_id' => '',
                      'so_luong_dat' => '',
                      'ghi_chu' => '',
                  ],
              ],
          ],
      ];
  }

  $formatQuantity = function ($value) {
      if ($value === null || $value === '') {
          return '';
      }

      $formatted = number_format((float) $value, 4, ',', '.');

      return rtrim(rtrim($formatted, '0'), ',');
  };

  $headerTitle = isset($donHang) && $donHang ? 'Cập nhật đơn hàng' : 'Thêm đơn hàng';
@endphp

<div class="card">
  <div class="card-header d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
    <div>
      <h5 class="mb-0">{{ $headerTitle }}</h5>
      <div class="text-muted small">Nhập thông tin kế hoạch và chi tiết đặt hàng.</div>
    </div>
    <a href="{{ $backRoute }}" class="btn btn-outline-secondary">
      <i class="icon-base bx bx-arrow-back me-1"></i> Quay lại
    </a>
  </div>

  <div class="card-body">
    <form action="{{ $action }}" method="POST" id="don-hang-form">
      @csrf
      @if ($method !== 'POST')
        @method($method)
      @endif

      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <label class="form-label" for="ngay_nhan">Ngày nhận <span class="text-danger">*</span></label>
          <input type="date" class="form-control @error('ngay_nhan') is-invalid @enderror" id="ngay_nhan"
            name="ngay_nhan" value="{{ old('ngay_nhan', optional($donHang?->ngay_nhan)->format('Y-m-d')) }}" required>
          @error('ngay_nhan')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-4">
          <label class="form-label" for="ma_don">Mã đơn <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('ma_don') is-invalid @enderror" id="ma_don" name="ma_don"
            value="{{ old('ma_don', $donHang?->ma_don) }}" maxlength="100" required>
          @error('ma_don')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-4">
          <label class="form-label" for="ma_kh">Mã KH <span class="text-danger">*</span></label>
          <input type="text" class="form-control @error('ma_kh') is-invalid @enderror" id="ma_kh" name="ma_kh"
            value="{{ old('ma_kh', $donHang?->ma_kh) }}" maxlength="100" required>
          @error('ma_kh')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-4">
          <label class="form-label" for="han_giao">Hạn giao</label>
          <input type="date" class="form-control @error('han_giao') is-invalid @enderror" id="han_giao"
            name="han_giao" value="{{ old('han_giao', optional($donHang?->han_giao)->format('Y-m-d')) }}">
          @error('han_giao')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-md-4">
          <label class="form-label" for="kenh_ban">Kênh bán</label>
          <input type="text" class="form-control @error('kenh_ban') is-invalid @enderror" id="kenh_ban"
            name="kenh_ban" value="{{ old('kenh_ban', $donHang?->kenh_ban) }}" maxlength="150">
          @error('kenh_ban')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="col-12">
          <label class="form-label" for="ghi_chu">Ghi chú</label>
          <textarea class="form-control @error('ghi_chu') is-invalid @enderror" id="ghi_chu" name="ghi_chu" rows="3">{{ old('ghi_chu', $donHang?->ghi_chu) }}</textarea>
          @error('ghi_chu')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-3">
        <div>
          <h6 class="mb-1">Chi tiết đơn hàng</h6>
          <div class="text-muted small">Chọn một Mã hàng rồi nhập nhiều màu, size và số lượng bên dưới.</div>
        </div>
      </div>

      @error('chi_tiets')
        <div class="alert alert-danger py-2">{{ $message }}</div>
      @enderror

      <div id="don-hang-detail-groups" class="d-flex flex-column gap-3">
        @foreach ($groups as $groupIndex => $group)
          @php
            $groupId = is_numeric($groupIndex) ? (int) $groupIndex : $loop->index;
            $groupErrorIndex = $group['_error_index'] ?? $groupId;
            $items = $group['items'] ?? [];

            if (!is_array($items) || $items === []) {
                $items = [
                    [
                        'mau_id' => '',
                        'size_id' => '',
                        'so_luong_dat' => '',
                        'ghi_chu' => '',
                    ],
                ];
            }
          @endphp
          <div class="border rounded p-3" data-don-hang-group data-group-index="{{ $groupId }}">
            <div class="row g-3 align-items-end mb-3">
              <div class="col-lg-8">
                <label class="form-label">Mã hàng <span class="text-danger">*</span></label>
                <select
                  class="form-select @error('chi_tiets.' . $groupErrorIndex . '.mat_hang_id') is-invalid @enderror"
                  name="chi_tiets[{{ $groupIndex }}][mat_hang_id]" required>
                  <option value="">-- Chọn mã hàng --</option>
                  @foreach ($matHangs as $matHang)
                    <option value="{{ $matHang->id }}" @selected((string) ($group['mat_hang_id'] ?? '') === (string) $matHang->id)>
                      {{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}
                    </option>
                  @endforeach
                </select>
                @error('chi_tiets.' . $groupErrorIndex . '.mat_hang_id')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-lg-4 d-flex gap-2 justify-content-lg-end">
                <button type="button" class="btn btn-outline-danger" data-remove-group title="Xóa mã hàng">
                  <i class="icon-base bx bx-trash"></i>
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 22%;">Màu <span class="text-danger">*</span></th>
                    <th style="width: 22%;">Size <span class="text-danger">*</span></th>
                    <th style="width: 18%;">SL đặt <span class="text-danger">*</span></th>
                    <th>Ghi chú</th>
                    <th style="width: 90px;">Xóa</th>
                  </tr>
                </thead>
                <tbody data-item-body>
                  @foreach ($items as $itemIndex => $item)
                    @php
                      $itemId = is_numeric($itemIndex) ? (int) $itemIndex : $loop->index;
                      $itemErrorIndex = $item['_error_index'] ?? $itemId;
                    @endphp
                    <tr data-don-hang-item data-item-index="{{ $itemId }}">
                      <td>
                        <select class="form-select @error('chi_tiets.' . $itemErrorIndex . '.mau_id') is-invalid @enderror"
                          name="chi_tiets[{{ $groupIndex }}][items][{{ $itemIndex }}][mau_id]" required>
                          <option value="">-- Chọn màu --</option>
                          @foreach ($maus as $mau)
                            <option value="{{ $mau->id }}" @selected((string) ($item['mau_id'] ?? '') === (string) $mau->id)>
                              {{ $mau->ten_mau }}
                            </option>
                          @endforeach
                        </select>
                        @error('chi_tiets.' . $itemErrorIndex . '.mau_id')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                      </td>
                      <td>
                        <select class="form-select @error('chi_tiets.' . $itemErrorIndex . '.size_id') is-invalid @enderror"
                          name="chi_tiets[{{ $groupIndex }}][items][{{ $itemIndex }}][size_id]" required>
                          <option value="">-- Chọn size --</option>
                          @foreach ($sizes as $size)
                            <option value="{{ $size->id }}" @selected((string) ($item['size_id'] ?? '') === (string) $size->id)>
                              {{ $size->ten_size }}
                            </option>
                          @endforeach
                        </select>
                        @error('chi_tiets.' . $itemErrorIndex . '.size_id')
                          <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                      </td>
                      <td>
                        <input type="text" inputmode="decimal" autocomplete="off"
                          class="form-control js-quantity-input @error('chi_tiets.' . $itemErrorIndex . '.so_luong_dat') is-invalid @enderror"
                          name="chi_tiets[{{ $groupIndex }}][items][{{ $itemIndex }}][so_luong_dat]"
                          value="{{ $formatQuantity($item['so_luong_dat'] ?? '') }}" required>
                        @error('chi_tiets.' . $itemErrorIndex . '.so_luong_dat')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </td>
                      <td>
                        <textarea class="form-control @error('chi_tiets.' . $itemErrorIndex . '.ghi_chu') is-invalid @enderror"
                          name="chi_tiets[{{ $groupIndex }}][items][{{ $itemIndex }}][ghi_chu]" rows="2">{{ $item['ghi_chu'] ?? '' }}</textarea>
                        @error('chi_tiets.' . $itemErrorIndex . '.ghi_chu')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </td>
                      <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-remove-item
                          title="Xóa màu/size">
                          <i class="icon-base bx bx-trash"></i>
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="button" class="btn btn-outline-primary" data-add-item>
                <i class="icon-base bx bx-plus me-1"></i> Thêm màu/size
              </button>
            </div>
          </div>
        @endforeach
      </div>

      <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-outline-primary" data-add-group>
          <i class="icon-base bx bx-plus me-1"></i> Thêm mã hàng
        </button>
      </div>

      <template id="don-hang-group-template">
        <div class="border rounded p-3" data-don-hang-group data-group-index="__GROUP__">
          <div class="row g-3 align-items-end mb-3">
            <div class="col-lg-8">
              <label class="form-label">Mã hàng <span class="text-danger">*</span></label>
              <select class="form-select" name="chi_tiets[__GROUP__][mat_hang_id]" required>
                <option value="">-- Chọn mã hàng --</option>
                @foreach ($matHangs as $matHang)
                  <option value="{{ $matHang->id }}">{{ $matHang->ma_hang }} - {{ $matHang->ten_hang }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-lg-4 d-flex gap-2 justify-content-lg-end">
              <button type="button" class="btn btn-outline-danger" data-remove-group title="Xóa mã hàng">
                <i class="icon-base bx bx-trash"></i>
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th style="width: 22%;">Màu <span class="text-danger">*</span></th>
                  <th style="width: 22%;">Size <span class="text-danger">*</span></th>
                  <th style="width: 18%;">SL đặt <span class="text-danger">*</span></th>
                  <th>Ghi chú</th>
                  <th style="width: 90px;">Xóa</th>
                </tr>
              </thead>
              <tbody data-item-body>
                <tr data-don-hang-item data-item-index="0">
                  <td>
                    <select class="form-select" name="chi_tiets[__GROUP__][items][0][mau_id]" required>
                      <option value="">-- Chọn màu --</option>
                      @foreach ($maus as $mau)
                        <option value="{{ $mau->id }}">{{ $mau->ten_mau }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <select class="form-select" name="chi_tiets[__GROUP__][items][0][size_id]" required>
                      <option value="">-- Chọn size --</option>
                      @foreach ($sizes as $size)
                        <option value="{{ $size->id }}">{{ $size->ten_size }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <input type="text" inputmode="decimal" autocomplete="off" class="form-control js-quantity-input"
                      name="chi_tiets[__GROUP__][items][0][so_luong_dat]" value="" required>
                  </td>
                  <td>
                    <textarea class="form-control" name="chi_tiets[__GROUP__][items][0][ghi_chu]" rows="2"></textarea>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-remove-item
                      title="Xóa màu/size">
                      <i class="icon-base bx bx-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-outline-primary" data-add-item>
              <i class="icon-base bx bx-plus me-1"></i> Thêm màu/size
            </button>
          </div>
        </div>
      </template>

      <template id="don-hang-item-template">
        <tr data-don-hang-item data-item-index="__ITEM__">
          <td>
            <select class="form-select" name="chi_tiets[__GROUP__][items][__ITEM__][mau_id]" required>
              <option value="">-- Chọn màu --</option>
              @foreach ($maus as $mau)
                <option value="{{ $mau->id }}">{{ $mau->ten_mau }}</option>
              @endforeach
            </select>
          </td>
          <td>
            <select class="form-select" name="chi_tiets[__GROUP__][items][__ITEM__][size_id]" required>
              <option value="">-- Chọn size --</option>
              @foreach ($sizes as $size)
                <option value="{{ $size->id }}">{{ $size->ten_size }}</option>
              @endforeach
            </select>
          </td>
          <td>
            <input type="text" inputmode="decimal" autocomplete="off" class="form-control js-quantity-input"
              name="chi_tiets[__GROUP__][items][__ITEM__][so_luong_dat]" value="" required>
          </td>
          <td>
            <textarea class="form-control" name="chi_tiets[__GROUP__][items][__ITEM__][ghi_chu]" rows="2"></textarea>
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-remove-item title="Xóa màu/size">
              <i class="icon-base bx bx-trash"></i>
            </button>
          </td>
        </tr>
      </template>

      <div class="d-flex gap-2 flex-wrap mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="icon-base bx bx-save me-1"></i> {{ $submitLabel }}
        </button>
        <a href="{{ $backRoute }}" class="btn btn-outline-secondary">Hủy</a>
      </div>
    </form>
  </div>
</div>
