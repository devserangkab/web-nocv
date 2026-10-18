<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * User dengan flag must_change_password dijebak di halaman ganti password:
 * request halaman apa pun di grup auth diarahkan kembali, kecuali
 * halaman reset itu sendiri dan logout.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        $allowed = ['auth.change-password', 'logout'];

        if (! in_array($request->route()?->getName(), $allowed, true)) {
            return redirect()->route('auth.change-password');
        }

        return $next($request);
    }
}
