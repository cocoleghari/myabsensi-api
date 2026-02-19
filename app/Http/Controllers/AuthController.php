<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register user baru
     */
    public function register(Request $request)
    {
        // Log untuk debugging
        Log::info('=' . str_repeat('=', 50));
        Log::info('REGISTER ATTEMPT');
        Log::info('Request data:', $request->all());
        Log::info('Headers:', $request->headers->all());
        
        try {
            // Validasi input
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,user',
            ]);

            Log::info('Data tervalidasi:', $validated);

            // HAPUS SEMUA PENGECEKAN UNTUK REGISTER USER
            // Semua role (admin dan user) bisa register tanpa login
            
            Log::info('Register diizinkan untuk role: ' . $validated['role']);

            // Buat user baru
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            Log::info('User registered successfully:', [
                'id' => $user->id, 
                'email' => $user->email,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Register berhasil',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Validasi gagal:', $e->errors());
            
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Register error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        Log::info('=' . str_repeat('=', 50));
        Log::info('LOGIN ATTEMPT:', ['email' => $request->email]);
        
        try {
            // Validasi input
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Cari user berdasarkan email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                Log::warning('Login failed: email not found', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak ditemukan'
                ], 404);
            }

            // Cek password
            if (!Hash::check($validated['password'], $user->password)) {
                Log::warning('Login failed: wrong password', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Password salah'
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
                ]
            ]);

        } catch (ValidationException $e) {
            Log::warning('Login validasi gagal:', $e->errors());
            
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server'
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            // Hapus semua token user
            $request->user()->tokens()->delete();
            
            Log::info('User logged out successfully:', ['user_id' => $request->user()->id]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil logout'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal logout'
            ], 500);
        }
    }

    /**
     * Get all users (admin only)
     */
    public function getUsers(Request $request)
    {
        try {
            // Cek apakah user adalah admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Ambil semua users
            $users = User::select('id', 'name', 'email', 'role')
                        ->orderBy('name')
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Get users error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data users'
            ], 500);
        }
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Cegah hapus diri sendiri
            if (auth()->id() == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa menghapus akun sendiri'
                ], 403);
            }

            $user->delete();

            Log::info('User deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete user error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user'
            ], 500);
        }
    }

    /**
     * Google login
     */
    public function googleLogin(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'name' => 'required|string',
            ]);

            // Cari atau buat user baru
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'role' => 'user',
                    'password' => Hash::make(Str::random(16)),
                ]
            );

            // Cek role
            if ($user->role !== 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Sign-In hanya untuk user'
                ], 403);
            }

            // Hapus token lama
            $user->tokens()->delete();
            
            // Buat token baru
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal login dengan Google'
            ], 500);
        }
    }
}