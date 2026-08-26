<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user();

        abort_unless($user, 401);

        /*
         * System administrators retain full access.
         */
        if ((bool) ($user->is_admin ?? false)) {
            return $next($request);
        }

        abort_unless(
            method_exists($user, 'hasPermission')
            && $user->hasPermission($permission),
            403
        );

        return $next($request);
    }
}
