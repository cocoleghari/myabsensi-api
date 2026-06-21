<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($request->status === 'nonaktif') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.list-akun', compact('users'));
    }

    public function create()
    {
        return view('admin.list-akun-form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'email' => ['nullable', 'email', Rule::unique('users', 'email')],
            'password' => 'required|string|min:6',
            'role' => 'required|in:employee,admin,hrd,manager',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.list-akun.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.list-akun-form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:employee,admin,hrd,manager',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'] ?? null;
        if (! empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->role = $data['role'];
        $user->is_active = $request->boolean('is_active');
        $user->save();

        return redirect()->route('admin.list-akun.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.list-akun.index')->with('error', 'Tidak dapat menghapus akun yang sedang login.');
        }

        if (Employee::where('user_id', $user->id)->exists()) {
            return redirect()->route('admin.list-akun.index')
                ->with('error', 'Akun ini masih terhubung dengan data karyawan. Kelola lewat menu Karyawan.');
        }

        $user->delete();

        return redirect()->route('admin.list-akun.index')->with('success', 'Akun berhasil dihapus.');
    }
}
