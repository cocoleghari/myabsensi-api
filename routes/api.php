<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\UserLokasiController;
use App\Http\Controllers\AdminAbsensiController; // Tambahkan import ini
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/google-login', [AuthController::class, 'googleLogin']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Login Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Logout untuk semua role
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile (untuk semua role)
    Route::get('/user/profile', function (Request $request) {
        return $request->user();
    });

    // ================= USER Routes (khusus role user) =================
    Route::middleware('role:user')->group(function () {
        // Lokasi untuk user
        Route::get('/user/lokasi', [UserLokasiController::class, 'getUserLokasi']);
        
        // Absensi - PASTIKAN INI POST
        Route::post('/user/absensi', [UserLokasiController::class, 'submitAbsensi']);
        
        // Riwayat absensi
        Route::get('/user/absensi/riwayat', [UserLokasiController::class, 'getRiwayatAbsensi']);
        
        // Cek status hari ini
        Route::get('/user/absensi/cek-hari-ini', [UserLokasiController::class, 'cekStatusHariIni']);
    });

    // ================= ADMIN Routes =================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Manajemen User
        Route::get('/users', [AuthController::class, 'getUsers']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
        
        // ===== ABSENSI ALL USER (TAMBAHAN BARU) =====
        // Ambil semua data absensi (bisa difilter berdasarkan user_id)
        Route::get('/absensi/all', [AdminAbsensiController::class, 'getAllAbsensi']);
        
        // Ambil semua user dengan role 'user' untuk dropdown filter
        Route::get('/users/all', [AdminAbsensiController::class, 'getAllUsers']);
    });
    
    // ================= LOKASI Routes (Admin only) =================
    Route::middleware('role:admin')->group(function () {
        Route::get('/lokasi', [LokasiController::class, 'index']);
        Route::post('/lokasi', [LokasiController::class, 'store']);
        Route::put('/lokasi/{id}', [LokasiController::class, 'update']);
        Route::delete('/lokasi/{id}', [LokasiController::class, 'destroy']);
        Route::get('/lokasi/users', [LokasiController::class, 'users']);
    });
});