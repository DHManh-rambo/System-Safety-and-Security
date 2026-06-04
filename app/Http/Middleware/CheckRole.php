<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // ---- DEMO BROKEN ACCESS CONTROL ----
        // Bỏ qua kiểm tra vai trò -> tất cả user đã login đều được phép truy cập mọi route
        // if (! in_array(Auth::user()->vai_tro, $roles)) {
        //     abort(403, 'Bạn không có quyền truy cập trang này.');
        // }

        return $next($request);
    }
}