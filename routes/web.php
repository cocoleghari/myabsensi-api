<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\AuthWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/admin/login', [AuthWebController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthWebController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthWebController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');

    // Pengaturan Akun
    Route::get('/list-akun', [AdminWebController::class, 'listAkun'])->name('list-akun');
    Route::get('/lokasi-user', [AdminWebController::class, 'lokasiUser'])->name('lokasi-user');
    Route::get('/pengaturan-lokasi', [AdminWebController::class, 'pengaturanLokasi'])->name('pengaturan-lokasi');

    // Pengaturan Karyawan
    Route::get('/department', [AdminWebController::class, 'department'])->name('department');
    Route::get('/karyawan', [AdminWebController::class, 'karyawan'])->name('karyawan');
    Route::get('/posisi', [AdminWebController::class, 'posisi'])->name('posisi');
    Route::get('/job-grade', [AdminWebController::class, 'jobGrade'])->name('job-grade');
    Route::get('/job-level', [AdminWebController::class, 'jobLevel'])->name('job-level');
    Route::get('/status-karyawan', [AdminWebController::class, 'statusKaryawan'])->name('status-karyawan');

    // Pengaturan Shift
    Route::get('/master-shift', [AdminWebController::class, 'masterShift'])->name('master-shift');
    Route::get('/pola-shift', [AdminWebController::class, 'polaShift'])->name('pola-shift');
    Route::get('/assign-shift', [AdminWebController::class, 'assignShift'])->name('assign-shift');

    // Laporan
    Route::get('/laporan-absensi', [AdminWebController::class, 'laporanAbsensi'])->name('laporan-absensi');
    Route::get('/laporan-aktivitas', [AdminWebController::class, 'laporanAktivitas'])->name('laporan-aktivitas');
    Route::get('/riwayat-user', [AdminWebController::class, 'riwayatUser'])->name('riwayat-user');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});
