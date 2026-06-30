<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthWebController extends Controller
{
    const WEB_ALLOWED_ROLES = ['superadmin', 'admin', 'hrd', 'manager', 'supervisor'];

    public function showLogin()
    {
        if (Auth::check() && in_array(Auth::user()->role, self::WEB_ALLOWED_ROLES)) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors(['email' => 'Email atau password salah.']);
        }

        if (! in_array(Auth::user()->role, self::WEB_ALLOWED_ROLES)) {
            Auth::logout();

            return back()->withErrors(['email' => 'Anda tidak memiliki akses ke panel admin.']);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
