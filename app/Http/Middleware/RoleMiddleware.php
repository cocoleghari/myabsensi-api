<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthorized - Silahkan login terlebih dahulu',
            ], 401);
        }

        // Support multiple roles: 'role:admin,superadmin,hrd'
        if (! in_array($request->user()->role, $roles)) {
            return response()->json([
                'message' => 'Akses ditolak - Anda tidak memiliki hak akses',
            ], 403);
        }

        return $next($request);
    }
}
