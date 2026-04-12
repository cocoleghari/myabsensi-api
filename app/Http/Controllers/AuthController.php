<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Log untuk debugging
        Log::info('='.str_repeat('=', 50));
        Log::info('REGISTER ATTEMPT (oleh admin)');
        Log::info('Request data:', $request->all());
        Log::info('Headers:', $request->headers->all());

        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,user',

                // tambahan
                'nik' => 'nullable|string|max:50',
                'nama_stempel' => 'nullable|string|max:255',
                'tgl_lahir' => 'nullable|date',
                'jk' => 'nullable|in:L,P',
                'alamat' => 'nullable|string',
                'jabatan' => 'nullable|string',
                'kantor' => 'nullable|string',
                'tgl_masuk' => 'nullable|date',
                'nomor_telp' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buat user baru
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'nik' => $request->nik,
                'nama_stempel' => $request->nama_stempel,
                'tgl_lahir' => $request->tgl_lahir,
                'jk' => $request->jk,
                'alamat' => $request->alamat,
                'jabatan' => $request->jabatan,
                'kantor' => $request->kantor,
                'tgl_masuk' => $request->tgl_masuk,
                'nomor_telp' => $request->nomor_telp,
            ]);

            Log::info('User registered successfully by admin:', [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'nik' => $user->nik,
                'nama_stempel' => $user->nama_stempel,
                'tgl_lahir' => $user->tgl_lahir,
                'jk' => $user->jk,
                'alamat' => $user->alamat,
                'jabatan' => $user->jabatan,
                'kantor' => $user->kantor,
                'tgl_masuk' => $user->tgl_masuk,
                'nomor_telp' => $user->nomor_telp,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Register berhasil',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'nik' => $user->nik,
                    'nama_stempel' => $user->nama_stempel,
                    'tgl_lahir' => $user->tgl_lahir,
                    'jk' => $user->jk,
                    'alamat' => $user->alamat,
                    'jabatan' => $user->jabatan,
                    'kantor' => $user->kantor,
                    'tgl_masuk' => $user->tgl_masuk,
                    'nomor_telp' => $user->nomor_telp,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Register error: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: '.$e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('LOGIN ATTEMPT:', ['email' => $request->email]);

        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                Log::warning('Login validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (! $user) {
                Log::warning('Login failed: email not found', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak ditemukan',
                ], 404);
            }

            if (! Hash::check($request->password, $user->password)) {
                Log::warning('Login failed: wrong password', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => 'Password salah',
                ], 401);
            }

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

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
                    'nik' => $user->nik,
                    'nama_stempel' => $user->nama_stempel,
                    'tgl_lahir' => $user->tgl_lahir,
                    'jk' => $user->jk,
                    'alamat' => $user->alamat,
                    'jabatan' => $user->jabatan,
                    'kantor' => $user->kantor,
                    'tgl_masuk' => $user->tgl_masuk,
                    'nomor_telp' => $user->nomor_telp,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            Log::info('User logged out successfully:', ['user_id' => $request->user()->id]);

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

    public function getUsers(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $users = User::select('id', 'name', 'email', 'role', 'nik', 'nama_stempel', 'tgl_lahir', 'jk', 'alamat', 'jabatan', 'kantor', 'tgl_masuk', 'nomor_telp')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);

        } catch (\Exception $e) {
            Log::error('Get users error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data users',
            ], 500);
        }
    }

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

            if ($user->email === 'superadmin@absensi.com') {
                Log::warning('Attempt to delete super admin:', [
                    'admin_id' => auth()->id(),
                    'target_email' => $user->email,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Akun Super Admin tidak dapat dihapus',
                ], 403);
            }

            if (auth()->id() == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus akun sendiri',
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

    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            // Validasi input
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
                'message' => 'Password admin berhasil diubah',
            ]);

        } catch (\Exception $e) {
            Log::error('Error change password admin: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password admin',
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }
}
