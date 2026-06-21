<?php

namespace App\Http\Controllers\TaiKhoan;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
  public function index(Request $request): View
  {
    $keyword = trim((string) $request->input('q'));

    $users = User::query()
      ->with('role')
      ->when($keyword !== '', function ($query) use ($keyword) {
        $query->where(function ($query) use ($keyword) {
          $query->where('name', 'like', "%{$keyword}%")
            ->orWhere('username', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('phone', 'like', "%{$keyword}%")
            ->orWhereHas('role', function ($query) use ($keyword) {
              $query->where('ma_vai_tro', 'like', "%{$keyword}%")
                ->orWhere('ten_vai_tro', 'like', "%{$keyword}%");
            });
        });
      })
      ->latest('id')
      ->paginate(10)
      ->withQueryString();

    return view('content.tai-khoan.user.index', compact('users', 'keyword'));
  }

  public function create(): View
  {
    return view('content.tai-khoan.user.create', [
      'roles' => $this->rolesForForm(),
    ]);
  }

  public function store(\App\Http\Requests\User\StoreUserRequest $request): RedirectResponse
  {
    $data = $request->validated();
    $data['email'] = blank($data['email'] ?? null) ? null : $data['email'];
    $data['password'] = Hash::make($data['password']);

    User::create($data);

    return redirect()
      ->route('user.index')
      ->with('success', 'Thêm tài khoản thành công.');
  }

  public function edit(User $user): View
  {
    $currentUserId = request()->user()?->getKey();

    return view('content.tai-khoan.user.edit', [
      'user' => $user->load('role'),
      'roles' => $this->rolesForForm(),
      'isCurrentUser' => $currentUserId === $user->getKey(),
    ]);
  }

  public function update(\App\Http\Requests\User\UpdateUserRequest $request, User $user): RedirectResponse
  {
    $data = $request->validated();
    $currentUserId = request()->user()?->getKey();

    $data['email'] = blank($data['email'] ?? null) ? null : $data['email'];

    if ($currentUserId === $user->getKey() && (int) $data['status'] === 0) {
      return back()->withInput()->with('error', 'Không thể khóa tài khoản đang đăng nhập.');
    }

    if (blank($data['password'] ?? null)) {
      unset($data['password']);
    } else {
      $data['password'] = Hash::make($data['password']);
    }

    $user->update($data);

    return redirect()
      ->route('user.index')
      ->with('success', 'Cập nhật tài khoản thành công.');
  }

  public function destroy(User $user): RedirectResponse
  {
    if (request()->user()?->getKey() === $user->getKey()) {
      return redirect()
        ->route('user.index')
        ->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
    }

    $user->delete();

    return redirect()
      ->route('user.index')
      ->with('success', 'Xóa tài khoản thành công.');
  }

  private function rolesForForm()
  {
    return Role::query()
      ->orderBy('ten_vai_tro')
      ->get();
  }
}
