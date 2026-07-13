<?php

namespace App\Http\Middleware;

use Closure;

class CheckPermission
{
    public function handle($request, Closure $next, $key)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        if (! $user->hasPermission($key)) {
            abort(403);
        }

        return $next($request);
    }
}