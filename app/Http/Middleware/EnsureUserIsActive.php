<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ($user->fresh()?->is_active ?? false)) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->is('login')) {
            return $next($request);
        }

        return redirect()
            ->route('login')
            ->withErrors(['identity' => 'Akun Anda telah dinonaktifkan.']);
    }
}
