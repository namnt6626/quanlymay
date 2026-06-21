<?php

namespace App\Http\Middleware;

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
      abort(403, 'Bạn không có quyền thực hiện chức năng này.');
    }

    return $next($request);
  }
}
