<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AccessRedirect;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginBasic extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $routeName = app(AccessRedirect::class)->firstAccessibleRoute(Auth::user());

            if ($routeName) {
                return redirect()->route($routeName);
            }

            abort(403, 'Tài khoản này chưa được cấp quyền truy cập hệ thống. Vui lòng liên hệ quản trị viên.');
        }

        return view('content.authentications.auth-login-basic');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = User::query()
            ->where('username', (string) $request->string('username'))
            ->first();

        if (! $user || ! Hash::check((string) $request->string('password'), $user->password)) {
            return back()
                ->withErrors(['username' => 'Tên đăng nhập hoặc mật khẩu không đúng.'])
                ->onlyInput('username');
        }

        if (! $user->status) {
            return back()
                ->withErrors(['username' => 'Tài khoản đã bị khóa.'])
                ->onlyInput('username');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->saveQuietly();

        ActivityLogger::log([
            'action' => 'LOGIN',
            'module' => 'Tài khoản',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => 'Đăng nhập tài khoản '.$user->username,
            'new_values' => [
                'username' => $user->username,
                'name' => $user->name,
            ],
        ]);

        $routeName = app(AccessRedirect::class)->firstAccessibleRoute($user);

        if (! $routeName) {
            abort(403, 'Tài khoản này chưa được cấp quyền truy cập hệ thống. Vui lòng liên hệ quản trị viên.');
        }

        return redirect()->intended(route($routeName));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            ActivityLogger::log([
                'action' => 'LOGOUT',
                'module' => 'Tài khoản',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => 'Đăng xuất tài khoản '.$user->username,
                'old_values' => [
                    'username' => $user->username,
                    'name' => $user->name,
                ],
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
