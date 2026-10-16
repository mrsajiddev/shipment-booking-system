<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ROUTE middleware, used as 'role:admin' (or 'role:admin,manager').
 * Extra values after ':' arrive as $roles.
 *
 * Middleware = a coarse check for a whole group of routes ("only admins here").
 * Policy     = a fine check for one record ("can THIS user cancel THIS shipment?").
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            abort(403, 'You need one of these roles: '.implode(', ', $roles).'.');
        }

        return $next($request);
    }
}
