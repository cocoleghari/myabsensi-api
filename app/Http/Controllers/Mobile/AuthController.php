<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // =========================================================================
    // REGISTER
    // Hanya membuat akun User (credentials + role).
    // Data karyawan (NIK, jabatan, dll) dibuat terpisah via EmployeeController.
    // =========================================================================

    public function register(Request $request)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('REGISTER ATTEMPT');
        Log::info('Request data:', $request->except('password'));

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:superadmin,admin,hrd,manager,employee',
                'company_id' => 'nullable|exists:companies,id',
            ]);

            if ($validator->fails()) {
                Log::warning('Register validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->filled('email') ? $request->email : null,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => true,
                'company_id' => $request->company_id ?? null,
            ]);

            Log::info('User registered successfully:', [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Register berhasil. Lengkapi data karyawan melalui menu manajemen karyawan.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Register error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // LOGIN
    // =========================================================================

    public function login(Request $request)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('LOGIN ATTEMPT:', ['email' => $request->email]);

        try {
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $input = $request->login;

            // Deteksi: jika mengandung '@' → cari via email, selain itu via nickname
            if (str_contains($input, '@')) {
                $user = User::where('email', $input)->first();
            } else {
                // Cari employee dengan nickname (case-insensitive), ambil user-nya
                $employee = \App\Models\Employee::whereRaw('LOWER(nickname) = ?', [strtolower($input)])
                    ->with('user')
                    ->first();
                $user = $employee?->user;
            }

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau nickname tidak ditemukan',
                ], 404);
            }

            if (! $user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif. Hubungi admin.',
                ], 403);
            }

            if (! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password salah',
                ], 401);
            }

            // Hapus token lama, buat token baru
            // $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load data employee jika ada
            $employee = $user->employee;

            Log::info('Login successful:', ['user_id' => $user->id, 'role' => $user->role]);

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'company_id' => $user->company_id,
                ],
                // Data employee (null jika belum dibuat profilnya)
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'nik' => $employee->nik,
                    'full_name' => $employee->full_name,
                    'nickname' => $employee->nickname,
                    'gender' => $employee->gender,
                    'photo_url' => $employee->photo_url,
                    'wajah_terdaftar' => $employee->wajah_terdaftar,
                    'phone' => $employee->phone,
                    'join_date' => $employee->join_date?->toDateString(),
                    'employment_type' => $employee->employment_type,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->name,
                    'company' => $employee->company?->name,
                    'company_id' => $user->company_id ??
                    $user->employee?->company_id ?? null,
                ] : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            Log::info('User logged out:', ['user_id' => $request->user()->id]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil logout',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal logout',
            ], 500);
        }
    }

    // =========================================================================
    // GET USERS (Admin)
    // Hanya return data dari tabel users (credentials).
    // Untuk data lengkap karyawan gunakan UserController / EmployeeController.
    // =========================================================================

    public function getUsers(Request $request)
    {
        $users = User::select('id', 'name', 'email', 'role', 'is_active', 'created_at')
            ->orderBy('name')
            ->paginate(50); // ← paginate, jangan get()

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    // =========================================================================
    // UPDATE USER (Admin)
    // PUT /admin/users/{id}
    // Body: name, email, role, is_active, password (opsional)
    // =========================================================================

    public function updateUser(Request $request, $id)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('UPDATE USER ATTEMPT:', ['target_id' => $id, 'by' => auth()->id()]);

        try {
            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            // Tidak boleh edit superadmin oleh selain superadmin
            if ($user->role === 'superadmin' && auth()->user()->role !== 'superadmin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun superadmin hanya dapat diubah oleh superadmin',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('users', 'email')->whereNotNull('email')->ignore($id),
                ],
                'role' => 'required|in:superadmin,admin,hrd,manager,employee',
                'is_active' => 'required|boolean',
                'password' => 'nullable|string|min:8',
                'company_id' => 'nullable|exists:companies,id',
            ]);

            if ($validator->fails()) {
                Log::warning('Update user validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Tidak boleh menonaktifkan atau mengganti role diri sendiri
            if (auth()->id() == $user->id) {
                if (! $request->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat menonaktifkan akun sendiri',
                    ], 403);
                }
                if ($request->role !== $user->role) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat mengubah role akun sendiri',
                    ], 403);
                }
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->role = $request->role;
            $user->is_active = $request->is_active;

            // Password hanya diupdate jika dikirim dan tidak kosong
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);

                // Hapus semua token lama agar user wajib login ulang
                $user->tokens()->delete();

                Log::info('Password reset for user:', ['id' => $user->id]);
            }

            $user->company_id = $request->company_id ?? $user->company_id;

            $user->save();

            Log::info('User updated successfully:', [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Akun '.$user->name.' berhasil diperbarui',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Update user error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DELETE USER (Admin)
    // =========================================================================

    public function deleteUser($id)
    {
        try {
            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            // Tidak boleh hapus superadmin
            if ($user->isSuperAdmin()) {
                Log::warning('Attempt to delete superadmin:', [
                    'admin_id' => auth()->id(),
                    'target_id' => $user->id,
                    'target_email' => $user->email,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Akun superadmin tidak dapat dihapus',
                ], 403);
            }

            // Tidak boleh hapus diri sendiri
            if (auth()->id() == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus akun sendiri',
                ], 403);
            }

            $user->delete();

            Log::info('User deleted:', ['id' => $id, 'deleted_by' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Delete user error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user',
            ], 500);
        }
    }

    // =========================================================================
    // CHANGE PASSWORD (User / Karyawan)
    // =========================================================================

    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|different:current_password',
                'confirm_password' => 'required|string|same:new_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak sesuai',
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info('Password changed for user: '.$user->id);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah',
            ]);

        } catch (\Exception $e) {
            Log::error('Error change password: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password',
            ], 500);
        }
    }

    // =========================================================================
    // CHANGE PASSWORD ADMIN
    // =========================================================================

    public function changePasswordAdmin(Request $request)
    {
        try {
            $admin = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|different:current_password',
                'confirm_password' => 'required|string|same:new_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (! Hash::check($request->current_password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak sesuai',
                ], 400);
            }

            $admin->password = Hash::make($request->new_password);
            $admin->save();

            Log::info('Password changed for admin: '.$admin->id);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah',
            ]);

        } catch (\Exception $e) {
            Log::error('Error change password admin: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password',
            ], 500);
        }
    }

    // =========================================================================
    // PROFILE
    // =========================================================================

    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            // Load employee dengan relasi, support semua role
            $employee = $user->employee()
                ?->with([
                    'department:id,name',
                    'position:id,name',
                    'company:id,name',
                    'status:id,label',
                ])
                ->first();

            $fotoWajahUrl = null;
            if ($employee?->foto_wajah_path) {
                $fotoWajahUrl = \Illuminate\Support\Facades\Storage::disk('public')
                    ->url($employee->foto_wajah_path);
            }

            $photoUrl = $employee?->photo_url ?? null;

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'nik' => $employee->nik,
                    'full_name' => $employee->full_name,
                    'nickname' => $employee->nickname,
                    'photo_url' => $photoUrl,
                    'foto_wajah_url' => $fotoWajahUrl,
                    'wajah_terdaftar' => $employee->wajah_terdaftar,
                    'department' => $employee->department?->name,
                    'position' => $employee->position?->name,
                    'company' => $employee->company?->name,
                    'company_id' => $employee->company_id,
                    'status' => $employee->status?->label,
                    'join_date' => $employee->join_date?->toDateString(),
                    'employment_type' => $employee->employment_type,
                    'phone' => $employee->phone,
                    'gender' => $employee->gender,
                ] : null,
                'foto_wajah_url' => $fotoWajahUrl,
                'photo_url' => $photoUrl,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Profile error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data profil',
            ], 500);
        }
    }
}
