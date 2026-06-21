<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginBasic extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard-analytics');
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

        return redirect()->intended(route('dashboard-analytics'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
