<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types)
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! in_array($user->user_type, $types, true)) {
            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
