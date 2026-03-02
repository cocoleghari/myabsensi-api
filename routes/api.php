<?php

use App\Http\Controllers\AdminAbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\PusatLokasiController;
use App\Http\Controllers\UserLokasiController; // <<< TAMBAHKAN INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (semua role)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', function (Request $request) {
        return $request->user();
    });
});

// User Routes
Route::middleware(['auth:sanctum', 'role:user'])->prefix('user')->group(function () {
    Route::get('/lokasi', [UserLokasiController::class, 'getUserLokasi']);
    Route::post('/absensi/masuk', [UserLokasiController::class, 'submitAbsensiMasuk']);
    Route::post('/absensi/pulang', [UserLokasiController::class, 'submitAbsensiPulang']);
    Route::get('/absensi/riwayat', [UserLokasiController::class, 'getRiwayatAbsensi']);
    Route::get('/absensi/cek-status', [UserLokasiController::class, 'cekStatusHariIni']);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // User management
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

    // Absensi management
    Route::get('/absensi/all', [AdminAbsensiController::class, 'getAllAbsensi']);
    Route::get('/users/all', [AdminAbsensiController::class, 'getAllUsers']);
    Route::delete('/absensi/{id}', [AdminAbsensiController::class, 'deleteAbsensi']);
    Route::get('/absensi/statistics', [AdminAbsensiController::class, 'getStatistics']);

    // ========== PUSAT LOKASI (TAMBAHAN BARU) ==========
    Route::get('/pusat-lokasi', [PusatLokasiController::class, 'index']);
    Route::post('/pusat-lokasi', [PusatLokasiController::class, 'store']);
    Route::get('/pusat-lokasi/{id}', [PusatLokasiController::class, 'show']);
    Route::put('/pusat-lokasi/{id}', [PusatLokasiController::class, 'update']);
    Route::delete('/pusat-lokasi/{id}', [PusatLokasiController::class, 'destroy']);
    Route::delete('/pusat-lokasi', [PusatLokasiController::class, 'destroyMultiple']); // Hapus multiple
});

// Lokasi Routes (Admin only) - Ini untuk lokasi USER (assignment ke user)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/lokasi', [LokasiController::class, 'index']);
    Route::post('/lokasi', [LokasiController::class, 'store']);
    Route::put('/lokasi/{id}', [LokasiController::class, 'update']);
    Route::delete('/lokasi/{id}', [LokasiController::class, 'destroy']);
    Route::get('/lokasi/users', [LokasiController::class, 'users']);
});
