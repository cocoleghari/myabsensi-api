<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login user
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

            // Cari user berdasarkan email
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                Log::warning('Login failed: email not found', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak ditemukan',
                ], 404);
            }

            // Cek password
            if (! Hash::check($request->password, $user->password)) {
                Log::warning('Login failed: wrong password', ['email' => $request->email]);

                return response()->json([
                    'success' => false,
                    'message' => 'Password salah',
                ], 401);
            }

            // Hapus token lama
            $user->tokens()->delete();

            // Buat token baru
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

    /**
     * Logout user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Hapus semua token user
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

    /**
     * Get all users (admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers(Request $request)
    {
        try {
            // Cek apakah user adalah admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Ambil semua users
            $users = User::select('id', 'name', 'email', 'role')
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

    /**
     * Delete user (admin only)
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
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

            // CEK APAKAH INI ADMIN PATEN (SUPER ADMIN)
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

            // Cegah admin menghapus diri sendiri
            if (auth()->id() == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus akun sendiri',
                ], 403);
            }

            // Hapus user
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

    /**
     * Ganti password untuk USER biasa
     * (hanya bisa diakses oleh role 'user')
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

            // Cek password lama
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak sesuai',
                ], 400);
            }

            // Update password
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

    /**
     * Ganti password untuk ADMIN
     * (hanya bisa diakses oleh role 'admin')
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePasswordAdmin(Request $request)
    {
        try {
            $admin = $request->user();

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

            // Cek password lama
            if (! Hash::check($request->current_password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak sesuai',
                ], 400);
            }

            // Update password
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

    /**
     * Get user profile (untuk testing)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }
}
