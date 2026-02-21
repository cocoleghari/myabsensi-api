<?php
// routes/api.php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\UserLokasiController;
use App\Http\Controllers\AdminAbsensiController;
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
        
        // Absensi Masuk
        Route::post('/user/absensi/masuk', [UserLokasiController::class, 'submitAbsensiMasuk']);
        
        // Absensi Pulang
        Route::post('/user/absensi/pulang', [UserLokasiController::class, 'submitAbsensiPulang']);
        
        // Riwayat absensi
        Route::get('/user/absensi/riwayat', [UserLokasiController::class, 'getRiwayatAbsensi']);
        
        // Cek status hari ini
        Route::get('/user/absensi/cek-status', [UserLokasiController::class, 'cekStatusHariIni']);
    });

    // ================= ADMIN Routes =================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getUsers']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
        Route::get('/absensi/all', [AdminAbsensiController::class, 'getAllAbsensi']);
        Route::get('/users/all', [AdminAbsensiController::class, 'getAllUsers']);

        Route::delete('/absensi/{id}', [AdminAbsensiController::class, 'deleteAbsensi']);
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