<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
  public function index(): View
  {
    $user = request()->user()->load('role');

    return view('content.authentications.profile.index', compact('user'));
  }

  public function update(UpdateProfileRequest $request): RedirectResponse
  {
    $user = $request->user();
    $data = $request->validated();

    $user->update([
      'name' => $data['name'],
      'email' => $data['email'] !== '' ? $data['email'] : null,
      'phone' => $data['phone'] !== '' ? $data['phone'] : null,
    ]);

    return redirect()
      ->route('profile.index')
      ->with('success', 'Cập nhật hồ sơ cá nhân thành công.');
  }

  public function changePassword(): View
  {
    $user = request()->user()->load('role');

    return view('content.authentications.profile.change-password', compact('user'));
  }

  public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
  {
    $user = $request->user();

    $user->forceFill([
      'password' => Hash::make($request->string('password')),
    ])->save();

    return redirect()
      ->route('profile.change-password')
      ->with('success', 'Đổi mật khẩu thành công.');
  }
}
