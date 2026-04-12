<?php

use App\Http\Controllers\AdminAbsensiController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\PusatLokasiController;
use App\Http\Controllers\TipeAktivitasController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLokasiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', function (Request $request) {
        return $request->user();
    });
});

// User Routes
Route::middleware(['auth:sanctum', 'role:user'])->prefix('user')->group(function () {
    Route::get('/lokasi', [UserLokasiController::class, 'getUserLokasi']);
    Route::post('/absensi/otomatis', [UserLokasiController::class, 'submitAbsensiOtomatis']);
    Route::get('/absensi/riwayat', [UserLokasiController::class, 'getRiwayatAbsensi']);
    Route::get('/absensi/cek-status', [UserLokasiController::class, 'cekStatusHariIni']);

    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/wajah/daftarkan', [UserLokasiController::class, 'daftarkanWajah']);
    // Verifikasi wajah saja (tanpa simpan absensi)
    Route::post('/wajah/verifikasi', [UserLokasiController::class, 'verifikasiWajahSaja']);
    // Tambah foto_wajah_url ke response profil
    Route::get('/profil', function (Request $request) {
        $user = $request->user();

        $fotoUrl = null;
        if ($user->foto_wajah_path) {
            // Pastikan menggunakan public disk
            $fotoUrl = Storage::disk('public')->url($user->foto_wajah_path);
        }

        return response()->json([
            ...$user->toArray(),
            'foto_wajah_url' => $fotoUrl,
            'photo_url' => $user->photo_url,
        ]);
    });

    // Foto profil
    Route::post('/upload-foto', [ProfilePhotoController::class, 'upload']);
    Route::delete('/hapus-foto', [ProfilePhotoController::class, 'destroy']);

    // Aktivitas
    Route::get('aktivitas', [AktivitasController::class, 'index']);
    Route::post('aktivitas', [AktivitasController::class, 'store']);
    Route::get('aktivitas/{id}', [AktivitasController::class, 'show']);
    Route::post('aktivitas/{id}', [AktivitasController::class, 'update']); // POST karena multipart
    Route::delete('aktivitas/{id}', [AktivitasController::class, 'destroy']);

    // Tipe Aktivitas
    Route::get('/tipe-aktivitas', [TipeAktivitasController::class, 'index']);

    // List Karyawan
    Route::get('/karyawan', [UserController::class, 'index']);
    Route::get('/jabatan-list', [UserController::class, 'jabatanList']);
    Route::get('/kantor-list', [UserController::class, 'kantorList']);
    Route::get('/karyawan/{id}', [UserController::class, 'show']);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::get('/users/all', [AdminAbsensiController::class, 'getAllUsers']);
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
    Route::get('/absensi/all', [AdminAbsensiController::class, 'getAllAbsensi']);
    Route::delete('/absensi/{id}', [AdminAbsensiController::class, 'deleteAbsensi']);
    Route::get('/absensi/statistics', [AdminAbsensiController::class, 'getStatistics']);

    // Pusat Lokasi
    Route::get('/pusat-lokasi', [PusatLokasiController::class, 'index']);
    Route::post('/pusat-lokasi', [PusatLokasiController::class, 'store']);
    Route::get('/pusat-lokasi/{id}', [PusatLokasiController::class, 'show']);
    Route::put('/pusat-lokasi/{id}', [PusatLokasiController::class, 'update']);
    Route::delete('/pusat-lokasi/{id}', [PusatLokasiController::class, 'destroy']);
    Route::delete('/pusat-lokasi', [PusatLokasiController::class, 'destroyMultiple']);

    // Ubah Password
    Route::post('/change-password', [AuthController::class, 'changePasswordAdmin']);

    // Tipe Aktivitas
    Route::post('/tipe-aktivitas', [TipeAktivitasController::class, 'store']);
    Route::put('/tipe-aktivitas/{id}', [TipeAktivitasController::class, 'update']);
    Route::delete('/tipe-aktivitas/{id}', [TipeAktivitasController::class, 'destroy']);

});

// Lokasi Routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/lokasi', [LokasiController::class, 'index']);
    Route::post('/lokasi', [LokasiController::class, 'store']);
    Route::put('/lokasi/{id}', [LokasiController::class, 'update']);
    Route::delete('/lokasi/{id}', [LokasiController::class, 'destroy']);
    Route::get('/lokasi/users', [LokasiController::class, 'users']);
    Route::get('/lokasi/cek-duplikat', [LokasiController::class, 'cekDuplikat']);

});
