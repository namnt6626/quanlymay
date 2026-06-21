<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'username' => [
        'required',
        'string',
        'max:100',
        Rule::unique('users', 'username')->whereNull('deleted_at'),
      ],
      'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
      'phone' => ['nullable', 'string', 'regex:/^(0)(3|5|7|8|9)[0-9]{8}$/'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
      'role_id' => ['required', 'exists:roles,id'],
      'status' => ['required', 'boolean'],
    ];
  }

  public function attributes(): array
  {
    return [
      'name' => 'Họ tên',
      'username' => 'Tên đăng nhập',
      'email' => 'Email',
      'phone' => 'Số điện thoại',
      'password' => 'Mật khẩu',
      'role_id' => 'Vai trò',
      'status' => 'Trạng thái',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Họ tên không được để trống.',
      'name.string' => 'Họ tên phải là chuỗi ký tự.',
      'name.max' => 'Họ tên không được vượt quá :max ký tự.',
      'username.required' => 'Tên đăng nhập không được để trống.',
      'username.string' => 'Tên đăng nhập phải là chuỗi ký tự.',
      'username.max' => 'Tên đăng nhập không được vượt quá :max ký tự.',
      'username.unique' => 'Tên đăng nhập đã tồn tại.',
      'email.email' => 'Email không đúng định dạng.',
      'email.max' => 'Email không được vượt quá :max ký tự.',
      'email.unique' => 'Email đã tồn tại.',
      'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
      'phone.regex' => 'Số điện thoại không đúng định dạng Việt Nam.',
      'password.required' => 'Mật khẩu không được để trống.',
      'password.string' => 'Mật khẩu phải là chuỗi ký tự.',
      'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
      'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
      'role_id.required' => 'Vai trò không được để trống.',
      'role_id.exists' => 'Vai trò không tồn tại.',
      'status.required' => 'Trạng thái không được để trống.',
      'status.boolean' => 'Trạng thái không hợp lệ.',
    ];
  }
}
