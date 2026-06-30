<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebAccessMiddleware
{
    const ALLOWED_ROLES = ['superadmin', 'admin', 'hrd', 'manager', 'supervisor'];

    const LIMITED_ROLES = ['manager', 'supervisor'];

    // Prefix route yang boleh diakses role terbatas
    const LIMITED_ROUTE_PREFIXES = [
        'admin.dashboard',
        'admin.laporan-absensi',
        'admin.laporan-aktivitas',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth()->user();

        if (! $user || ! in_array($user->role, self::ALLOWED_ROLES)) {
            Auth()->logout();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Anda tidak memiliki akses ke panel admin.']);
        }

        // Role terbatas: cek apakah route yang diakses diizinkan
        if (in_array($user->role, self::LIMITED_ROLES)) {
            $routeName = $request->route()?->getName() ?? '';
            $diizinkan = false;

            foreach (self::LIMITED_ROUTE_PREFIXES as $prefix) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                    $diizinkan = true;
                    break;
                }
            }

            if (! $diizinkan) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }

        return $next($request);
    }
}
