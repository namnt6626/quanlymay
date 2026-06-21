<?php

namespace App\Http\Requests\PhanBoMay;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePhanBoMayRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  protected function prepareForValidation(): void
  {
    $this->merge([
      'so_luong_giao' => $this->normalizeNumberInput($this->input('so_luong_giao')),
    ]);
  }

  public function rules(): array
  {
    return [
      'ngay_phan_bo' => ['required', 'date'],
      'don_vi_may_id' => [
        'required',
        'integer',
        Rule::exists('dm_don_vi_may', 'id')->whereNull('deleted_at'),
      ],
      'so_luong_giao' => ['required', 'numeric', 'gt:0'],
      'ghi_chu' => ['nullable', 'string'],
    ];
  }

  public function attributes(): array
  {
    return [
      'ngay_phan_bo' => 'Ngày phân bổ',
      'don_vi_may_id' => 'Đơn vị may',
      'so_luong_giao' => 'Số lượng giao',
      'ghi_chu' => 'Ghi chú',
    ];
  }

  public function messages(): array
  {
    return [
      'ngay_phan_bo.required' => 'Ngày phân bổ là bắt buộc.',
      'ngay_phan_bo.date' => 'Ngày phân bổ không đúng định dạng ngày.',
      'don_vi_may_id.required' => 'Đơn vị may là bắt buộc.',
      'don_vi_may_id.integer' => 'Đơn vị may không hợp lệ.',
      'don_vi_may_id.exists' => 'Đơn vị may đã chọn không tồn tại.',
      'so_luong_giao.required' => 'Số lượng giao là bắt buộc.',
      'so_luong_giao.numeric' => 'Số lượng giao phải là số.',
      'so_luong_giao.gt' => 'Số lượng giao phải lớn hơn :value.',
      'ghi_chu.string' => 'Ghi chú phải là chuỗi ký tự.',
    ];
  }

  private function normalizeNumberInput(mixed $value): mixed
  {
    if ($value === null) {
      return null;
    }

    $value = trim((string) $value);

    if ($value === '') {
      return null;
    }

    $value = preg_replace('/\s+/', '', $value) ?? $value;

    $commaCount = substr_count($value, ',');
    $dotCount = substr_count($value, '.');

    if ($commaCount > 0 && $dotCount > 0) {
      $decimalSeparator = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
      $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';

      $value = str_replace($thousandSeparator, '', $value);
      $value = str_replace($decimalSeparator, '.', $value);
    } elseif ($commaCount > 0) {
      $parts = explode(',', $value);

      if ($commaCount === 1 && strlen(end($parts)) !== 3) {
        $value = str_replace(',', '.', $value);
      } else {
        $value = str_replace(',', '', $value);
      }
    } elseif ($dotCount > 0) {
      $parts = explode('.', $value);

      if (! ($dotCount === 1 && strlen(end($parts)) !== 3)) {
        $value = str_replace('.', '', $value);
      }
    }

    $value = preg_replace('/[^0-9.\-]/', '', $value) ?? $value;

    if (substr_count($value, '.') > 1) {
      $segments = explode('.', $value);
      $value = array_shift($segments) . '.' . implode('', $segments);
    }

    return $value;
  }
}
