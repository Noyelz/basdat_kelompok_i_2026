<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! $request->user()->is_active || ! in_array($request->user()->role, $roles, true)) {
            abort(403);
        }

        if ($request->user()->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
