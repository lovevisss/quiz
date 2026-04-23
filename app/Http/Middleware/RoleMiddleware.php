<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        \Illuminate\Support\Facades\Log::info('RoleMiddleware ENTRY');
        $user = Auth::user();
        \Illuminate\Support\Facades\Log::info('RoleMiddleware user', ['user_id' => $user?->id, 'is_admin' => $user?->is_admin]);
        // Only support 'admin' role for now
        if ($role === 'admin') {
            if (!$user || !$user->is_admin) {
                abort(403, 'Unauthorized');
            }
        } else {
            // All other roles are forbidden (future-proof: can add more later)
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}