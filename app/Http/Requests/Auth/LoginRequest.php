<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'username' => ['required', 'string', 'max:100'],
      'password' => ['required', 'string'],
      'remember' => ['nullable', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'username' => 'Tên đăng nhập',
      'password' => 'Mật khẩu',
      'remember' => 'Ghi nhớ đăng nhập',
    ];
  }

  public function messages(): array
  {
    return [
      'username.required' => 'Tên đăng nhập không được để trống.',
      'username.string' => 'Tên đăng nhập phải là chuỗi ký tự.',
      'username.max' => 'Tên đăng nhập không được vượt quá :max ký tự.',
      'password.required' => 'Mật khẩu không được để trống.',
      'password.string' => 'Mật khẩu phải là chuỗi ký tự.',
      'remember.boolean' => 'Ghi nhớ đăng nhập không hợp lệ.',
    ];
  }
}
