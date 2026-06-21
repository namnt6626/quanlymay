<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    /** @var \App\Models\User|null $user */
    $user = $this->user();

    return [
      'name' => ['required', 'string', 'max:255'],
      'email' => [
        'nullable',
        'email',
        'max:255',
        Rule::unique('users', 'email')
          ->ignore($user instanceof User ? $user->getKey() : null)
          ->whereNull('deleted_at'),
      ],
      'phone' => ['nullable', 'string', 'regex:/^(0)(3|5|7|8|9)[0-9]{8}$/'],
    ];
  }

  public function attributes(): array
  {
    return [
      'name' => 'Họ tên',
      'email' => 'Email',
      'phone' => 'Số điện thoại',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Họ tên không được để trống.',
      'name.string' => 'Họ tên phải là chuỗi ký tự.',
      'name.max' => 'Họ tên không được vượt quá :max ký tự.',
      'email.email' => 'Email không đúng định dạng.',
      'email.max' => 'Email không được vượt quá :max ký tự.',
      'email.unique' => 'Email đã tồn tại.',
      'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
      'phone.regex' => 'Số điện thoại không đúng định dạng Việt Nam.',
    ];
  }
}
