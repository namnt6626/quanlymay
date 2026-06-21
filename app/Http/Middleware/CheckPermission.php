<?php

namespace App\Http\Middleware;

use App\Support\AccessRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissionCode): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Bạn không có quyền thực hiện chức năng này.');
        }

        if (! Gate::forUser($user)->allows('permission', $permissionCode)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn không có quyền thực hiện thao tác này.',
                ], 403);
            }

            $routeName = app(AccessRedirect::class)->firstAccessibleRoute($user);
            $currentRouteName = $request->route()?->getName();

            if ($routeName && $routeName !== $currentRouteName) {
                return redirect()->route($routeName);
            }

            abort(403, 'Tài khoản này chưa được cấp quyền truy cập hệ thống. Vui lòng liên hệ quản trị viên.');
        }

        return $next($request);
    }
}
