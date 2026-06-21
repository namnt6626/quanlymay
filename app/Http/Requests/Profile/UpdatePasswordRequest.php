<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'current_password' => ['required', 'current_password:web'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
  }

  public function attributes(): array
  {
    return [
      'current_password' => 'Mật khẩu hiện tại',
      'password' => 'Mật khẩu mới',
    ];
  }

  public function messages(): array
  {
    return [
      'current_password.required' => 'Mật khẩu hiện tại không được để trống.',
      'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
      'password.required' => 'Mật khẩu mới không được để trống.',
      'password.string' => 'Mật khẩu mới phải là chuỗi ký tự.',
      'password.min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',
      'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
    ];
  }
}
