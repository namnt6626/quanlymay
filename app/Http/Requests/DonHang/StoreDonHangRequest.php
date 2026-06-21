<?php

namespace App\Http\Requests\DonHang;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonHangRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  protected function prepareForValidation(): void
  {
    $chiTiets = $this->input('chi_tiets', []);

    if (! is_array($chiTiets)) {
      return;
    }

    $normalizedChiTiets = [];

    foreach ($chiTiets as $chiTiet) {
      if (! is_array($chiTiet)) {
        continue;
      }

      if (isset($chiTiet['items']) && is_array($chiTiet['items'])) {
        foreach ($chiTiet['items'] as $item) {
          if (! is_array($item)) {
            continue;
          }

          $normalizedChiTiets[] = [
            'mat_hang_id' => $chiTiet['mat_hang_id'] ?? null,
            'mau_id' => $item['mau_id'] ?? null,
            'size_id' => $item['size_id'] ?? null,
            'so_luong_dat' => $this->normalizeNumber($item['so_luong_dat'] ?? null),
            'ghi_chu' => $item['ghi_chu'] ?? null,
          ];
        }

        continue;
      }

      $chiTiet['so_luong_dat'] = $this->normalizeNumber($chiTiet['so_luong_dat'] ?? null);
      $normalizedChiTiets[] = $chiTiet;
    }

    $this->merge([
      'chi_tiets' => $normalizedChiTiets,
    ]);
  }

  public function rules(): array
  {
    return [
      'ngay_nhan' => ['required', 'date'],
      'ma_don' => [
        'required',
        'string',
        'max:100',
        Rule::unique('don_hangs', 'ma_don')->whereNull('deleted_at'),
      ],
      'ma_kh' => ['required', 'string', 'max:100'],
      'han_giao' => ['nullable', 'date'],
      'kenh_ban' => ['nullable', 'string', 'max:150'],
      'ghi_chu' => ['nullable', 'string'],
      'chi_tiets' => ['required', 'array', 'min:1'],
      'chi_tiets.*.mat_hang_id' => ['required', Rule::exists('dm_mat_hang', 'id')->whereNull('deleted_at')],
      'chi_tiets.*.mau_id' => ['required', Rule::exists('dm_mau', 'id')->whereNull('deleted_at')],
      'chi_tiets.*.size_id' => ['required', Rule::exists('dm_size', 'id')->whereNull('deleted_at')],
      'chi_tiets.*.so_luong_dat' => ['required', 'numeric', 'gt:0'],
      'chi_tiets.*.ghi_chu' => ['nullable', 'string'],
    ];
  }

  public function attributes(): array
  {
    return [
      'ngay_nhan' => 'Ngày nhận',
      'ma_don' => 'Mã đơn',
      'ma_kh' => 'Mã KH',
      'han_giao' => 'Hạn giao',
      'kenh_ban' => 'Kênh bán',
      'ghi_chu' => 'Ghi chú',
      'chi_tiets' => 'Dòng chi tiết',
      'chi_tiets.*.mat_hang_id' => 'Mã hàng',
      'chi_tiets.*.mau_id' => 'Màu',
      'chi_tiets.*.size_id' => 'Size',
      'chi_tiets.*.so_luong_dat' => 'SL đặt',
      'chi_tiets.*.ghi_chu' => 'Ghi chú dòng chi tiết',
    ];
  }

  public function messages(): array
  {
    return [
      'ngay_nhan.required' => 'Ngày nhận là bắt buộc.',
      'ngay_nhan.date' => 'Ngày nhận không hợp lệ.',
      'ma_don.required' => 'Mã đơn là bắt buộc.',
      'ma_don.string' => 'Mã đơn phải là chuỗi ký tự.',
      'ma_don.max' => 'Mã đơn không được vượt quá :max ký tự.',
      'ma_don.unique' => 'Mã đơn đã tồn tại trong hệ thống.',
      'ma_kh.required' => 'Mã KH là bắt buộc.',
      'ma_kh.string' => 'Mã KH phải là chuỗi ký tự.',
      'ma_kh.max' => 'Mã KH không được vượt quá :max ký tự.',
      'han_giao.date' => 'Hạn giao không hợp lệ.',
      'kenh_ban.string' => 'Kênh bán phải là chuỗi ký tự.',
      'kenh_ban.max' => 'Kênh bán không được vượt quá :max ký tự.',
      'ghi_chu.string' => 'Ghi chú phải là chuỗi ký tự.',
      'chi_tiets.required' => 'Cần có ít nhất một dòng chi tiết.',
      'chi_tiets.array' => 'Dòng chi tiết không hợp lệ.',
      'chi_tiets.min' => 'Cần có ít nhất một dòng chi tiết.',
      'chi_tiets.*.mat_hang_id.required' => 'Mã hàng là bắt buộc.',
      'chi_tiets.*.mat_hang_id.exists' => 'Mã hàng không tồn tại hoặc đã bị xóa.',
      'chi_tiets.*.mau_id.required' => 'Màu là bắt buộc.',
      'chi_tiets.*.mau_id.exists' => 'Màu không tồn tại hoặc đã bị xóa.',
      'chi_tiets.*.size_id.required' => 'Size là bắt buộc.',
      'chi_tiets.*.size_id.exists' => 'Size không tồn tại hoặc đã bị xóa.',
      'chi_tiets.*.so_luong_dat.required' => 'SL đặt là bắt buộc.',
      'chi_tiets.*.so_luong_dat.numeric' => 'SL đặt phải là số.',
      'chi_tiets.*.so_luong_dat.gt' => 'SL đặt phải lớn hơn 0.',
      'chi_tiets.*.ghi_chu.string' => 'Ghi chú dòng chi tiết phải là chuỗi ký tự.',
    ];
  }

  private function normalizeNumber(mixed $value): ?string
  {
    $text = trim((string) $value);

    if ($text === '') {
      return null;
    }

    $text = preg_replace('/\s+/', '', $text) ?? '';

    $commaCount = substr_count($text, ',');
    $dotCount = substr_count($text, '.');

    if ($commaCount > 0 && $dotCount > 0) {
      $decimalSeparator = strrpos($text, ',') > strrpos($text, '.') ? ',' : '.';
      $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
      $text = str_replace($thousandSeparator, '', $text);
      $text = str_replace($decimalSeparator, '.', $text);
    } elseif ($commaCount > 0) {
      $parts = explode(',', $text);

      if ($commaCount === 1 && strlen(end($parts) ?: '') !== 3) {
        $text = str_replace(',', '.', $text);
      } else {
        $text = str_replace(',', '', $text);
      }
    } elseif ($dotCount > 0) {
      $parts = explode('.', $text);

      if (! ($dotCount === 1 && strlen(end($parts) ?: '') !== 3)) {
        $text = str_replace('.', '', $text);
      }
    }

    $text = preg_replace('/[^\d.\-]/', '', $text) ?? '';

    $firstDotIndex = strpos($text, '.');
    if ($firstDotIndex !== false) {
      $text = substr($text, 0, $firstDotIndex + 1) . str_replace('.', '', substr($text, $firstDotIndex + 1));
    }

    return $text === '' ? null : $text;
  }
}
