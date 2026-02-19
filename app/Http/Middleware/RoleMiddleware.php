<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized - Silahkan login terlebih dahulu'
            ], 401);
        }

        if ($request->user()->role !== $role) {
            return response()->json([
                'message' => 'Akses ditolak - Anda tidak memiliki hak akses sebagai ' . $role
            ], 403);
        }

        return $next($request);
    }
}