<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            // Allow access only to password change routes and logout
            if (! $request->routeIs('password.first-change', 'password.first-change.update', 'logout')) {
                return redirect()->route('password.first-change');
            }
        }

        return $next($request);
    }
}
