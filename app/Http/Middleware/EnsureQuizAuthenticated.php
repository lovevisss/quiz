<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureQuizAuthenticated
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401, 'Unauthenticated.');
        }

        return redirect()->route('cas.redirect', [
            'return' => $request->getRequestUri(),
        ]);
    }
}
