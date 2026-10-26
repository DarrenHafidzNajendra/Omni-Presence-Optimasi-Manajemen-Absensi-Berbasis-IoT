<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = $user === null ? null : UserRole::tryFrom((string) $user->role);

        abort_unless($role !== null && in_array($role->value, $roles, true), 403);

        return $next($request);
    }
}
