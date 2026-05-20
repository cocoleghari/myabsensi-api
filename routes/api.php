<?php

use App\Http\Controllers\AdminAbsensiController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;  // ← BARU (ganti LokasiController)
use App\Http\Controllers\EmployeeExportImportController;
use App\Http\Controllers\EmployeePusatLokasiController;
use App\Http\Controllers\EmployeeShiftController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\JobGradeController;
use App\Http\Controllers\JobLevelController;
use App\Http\Controllers\LaporanAbsensiController;
use App\Http\Controllers\LaporanAktivitasController;
use App\Http\Controllers\NotificationUserController;
use App\Http\Controllers\PermintaanAbsenController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\PusatLokasiController;
use App\Http\Controllers\PusatLokasiExportImportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftWeeklyPatternController;
use App\Http\Controllers\TipeAktivitasController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLokasiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;                  // ← BARU (dipakai di closure /profil)

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC
// ─────────────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ─────────────────────────────────────────────────────────────────────────────
// PROTECTED (semua role)
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', fn (Request $request) => $request->user());
});

// ─────────────────────────────────────────────────────────────────────────────
// USER / KARYAWAN ROUTES
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:employee'])->prefix('user')->group(function () {

    // Absensi
    Route::get('/lokasi', [UserLokasiController::class, 'getUserLokasi']);
    Route::post('/absensi/otomatis', [UserLokasiController::class, 'submitAbsensiOtomatis']);
    Route::get('/absensi/riwayat', [UserLokasiController::class, 'getRiwayatAbsensi']);
    Route::get('/absensi/cek-status', [UserLokasiController::class, 'cekStatusHariIni']);

    // Wajah
    Route::post('/wajah/daftarkan', [UserLokasiController::class, 'daftarkanWajah']);
    Route::post('/wajah/verifikasi', [UserLokasiController::class, 'verifikasiWajahSaja']);

    // Profil
    Route::get('/profil', function (Request $request) {
        $employee = $request->user()
            ->employee()
            ->with([
                'department:id,name',
                'position:id,name',
                'company:id,name',
                'status:id,label',
            ])
            ->first();

        $fotoWajahUrl = null;
        if ($employee?->foto_wajah_path) {
            $fotoWajahUrl = Storage::disk('public')->url($employee->foto_wajah_path);
        }

        // Bangun photo_url secara eksplisit — jangan andalkan toArray()
        $photoUrl = null;
        if ($employee?->photo_url) {
            $photoUrl = $employee->photo_url;
        }

        $empArray = $employee ? [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'nik' => $employee->nik,
            'full_name' => $employee->full_name,
            'nickname' => $employee->nickname,
            'photo_url' => $photoUrl,  // ← eksplisit
            'foto_wajah_url' => $fotoWajahUrl,
            'wajah_terdaftar' => $employee->wajah_terdaftar,
            'date_of_birth' => $employee->date_of_birth?->toDateString(),
            'place_of_birth' => $employee->place_of_birth,
            'gender' => $employee->gender,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'city' => $employee->city,
            'province' => $employee->province,
            'postal_code' => $employee->postal_code,
            'marital_status' => $employee->marital_status,
            'religion' => $employee->religion,
            'blood_type' => $employee->blood_type,
            'ktp_number' => $employee->ktp_number,
            'department' => $employee->department?->name,
            'position' => $employee->position?->name,
            'company' => $employee->company?->name,
            'company_id' => $employee->company_id,
            'status' => $employee->status?->label,
            'join_date' => $employee->join_date?->toDateString(),
            'employment_type' => $employee->employment_type,
            'contract_end_date' => $employee->contract_end_date?->toDateString(),
            'last_education' => $employee->last_education,
            'last_education_major' => $employee->last_education_major,
            'last_education_institution' => $employee->last_education_institution,
            'emergency_contact_name' => $employee->emergency_contact_name,
            'emergency_contact_phone' => $employee->emergency_contact_phone,
            'emergency_contact_relation' => $employee->emergency_contact_relation,
            'npwp' => $employee->npwp,
            'bpjs_kesehatan' => $employee->bpjs_kesehatan,
            'bpjs_ketenagakerjaan' => $employee->bpjs_ketenagakerjaan,
            'bank_name' => $employee->bank_name,
            'bank_account_number' => $employee->bank_account_number,
            'bank_account_name' => $employee->bank_account_name,
        ] : null;

        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'username' => $request->user()->username,
            'role' => $request->user()->role,
            'is_active' => $request->user()->is_active,
            'employee' => $empArray,
            'foto_wajah_url' => $fotoWajahUrl,
            'photo_url' => $photoUrl,
        ]);
    });

    Route::post('/change-password', [AuthController::class, 'changePassword']);

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

    // Notifikasi
    Route::post('permintaan-absen', [PermintaanAbsenController::class, 'store']);
    Route::get('permintaan-absen/riwayat', [PermintaanAbsenController::class, 'riwayat']);
    Route::get('notifications', [NotificationUserController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationUserController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationUserController::class, 'markAllRead']);
    Route::get('permintaan-absen/{id}', [PermintaanAbsenController::class, 'show']);
    Route::patch('permintaan-absen/{id}/proses', [PermintaanAbsenController::class, 'proses']);
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // Auth & User management
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::put('/users/{id}', [AuthController::class, 'updateUser']);
    Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);
    Route::post('/change-password', [AuthController::class, 'changePasswordAdmin']);

    // Absensi
    Route::get('/absensi/statistics', [AdminAbsensiController::class, 'getStatistics']); // ← HARUS sebelum {id}
    Route::get('/laporan-absensi/summary', [LaporanAbsensiController::class, 'summary']);
    Route::get('/laporan-absensi/export', [LaporanAbsensiController::class, 'export']);
    Route::get('/absensi/all', [AdminAbsensiController::class, 'getAllAbsensi']);
    Route::delete('/absensi/{id}', [AdminAbsensiController::class, 'deleteAbsensi']);

    // Dropdown karyawan untuk filter absensi
    // DIUBAH: getAllUsers (dari User) → getAllEmployees (dari Employee)
    Route::get('/employees', [AdminAbsensiController::class, 'getAllEmployees']);

    // ── Export / Import ── HARUS DI ATAS route {id} ──────────────────────────
    Route::get('/pusat-lokasi/export', [PusatLokasiExportImportController::class, 'export']);
    Route::get('/pusat-lokasi/import-template', [PusatLokasiExportImportController::class, 'downloadTemplate']);
    Route::post('/pusat-lokasi/import', [PusatLokasiExportImportController::class, 'import']);

    Route::post('/pusat-lokasi/{id}/bulk-assign-employees', [PusatLokasiController::class, 'bulkAssignEmployees']);

    // Pusat Lokasi (master data)
    Route::delete('/pusat-lokasi', [PusatLokasiController::class, 'destroyMultiple']); // ← HARUS sebelum {id}
    Route::get('/pusat-lokasi', [PusatLokasiController::class, 'index']);
    Route::post('/pusat-lokasi', [PusatLokasiController::class, 'store']);
    Route::get('/pusat-lokasi/{id}', [PusatLokasiController::class, 'show']);
    Route::put('/pusat-lokasi/{id}', [PusatLokasiController::class, 'update']);
    Route::delete('/pusat-lokasi/{id}', [PusatLokasiController::class, 'destroy']);

    // ── BARU: Relasi karyawan ↔ pusat lokasi ──────────────────────────────────
    // Menggantikan semua route /lokasi lama
    Route::get('/employee-lokasi', [EmployeePusatLokasiController::class, 'index']);
    Route::post('/employee-lokasi', [EmployeePusatLokasiController::class, 'store']);
    Route::post('/employee-lokasi/cek-duplikat', [EmployeePusatLokasiController::class, 'cekDuplikat']); // ← sebelum {id}
    Route::get('/employee-lokasi/employee/{id}', [EmployeePusatLokasiController::class, 'byEmployee']);  // ← sebelum {id}
    Route::put('/employee-lokasi/{id}', [EmployeePusatLokasiController::class, 'update']);
    Route::delete('/employee-lokasi/{id}', [EmployeePusatLokasiController::class, 'destroy']);
    Route::get('/employees-lokasi-list', [EmployeePusatLokasiController::class, 'employees']);

    // Employees (profil karyawan — terpisah dari /users yang hanya credentials)
    Route::get('/employees/options', [EmployeeController::class, 'options']); // ← HARUS sebelum {id}
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    // Dropdown
    Route::get('/employees-dropdown', [EmployeeController::class, 'dropdown']);

    Route::get('/employees/export', [EmployeeExportImportController::class, 'export']);
    Route::get('/employees/import-template', [EmployeeExportImportController::class, 'downloadTemplate']);
    Route::post('/employees/import', [EmployeeExportImportController::class, 'import']);

    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

    // Tipe Aktivitas
    Route::post('/tipe-aktivitas', [TipeAktivitasController::class, 'store']);
    Route::put('/tipe-aktivitas/{id}', [TipeAktivitasController::class, 'update']);
    Route::delete('/tipe-aktivitas/{id}', [TipeAktivitasController::class, 'destroy']);

    // Department
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/departments/export', [DepartmentController::class, 'export']);
    Route::get('/departments/import-template', [DepartmentController::class, 'importTemplate']);
    Route::post('/departments/import', [DepartmentController::class, 'import']);
    Route::get('/departments/{id}', [DepartmentController::class, 'show']);
    Route::put('/departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
    Route::get('/departments-tree', [DepartmentController::class, 'tree']); // hierarki nested
    Route::post('/departments/{id}/bulk-assign', [DepartmentController::class, 'bulkAssign']);
    Route::get('/employees-list', [DepartmentController::class, 'employeesForAssign']);

    // Company
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    Route::get('/companies-list', [CompanyController::class, 'list']); // untuk dropdown

    // Position
    Route::get('/positions-list', [PositionController::class, 'list']);
    Route::get('/positions/export', [PositionController::class, 'export']);          // ← BARU
    Route::get('/positions/import-template', [PositionController::class, 'importTemplate']);  // ← BARU
    Route::post('/positions/import', [PositionController::class, 'import']);           // ← BARU
    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store']);
    Route::get('/positions/{position}', [PositionController::class, 'show']);
    Route::put('/positions/{position}', [PositionController::class, 'update']);
    Route::delete('/positions/{position}', [PositionController::class, 'destroy']);

    // Job Grade
    Route::get('/job-grades-list', [JobGradeController::class, 'list']);   // ← sebelum {jobGrade}
    Route::get('/job-grades/export', [JobGradeController::class, 'export']);
    Route::get('/job-grades/import-template', [JobGradeController::class, 'importTemplate']);
    Route::post('/job-grades/import', [JobGradeController::class, 'import']);
    Route::get('/job-grades', [JobGradeController::class, 'index']);
    Route::post('/job-grades', [JobGradeController::class, 'store']);
    Route::get('/job-grades/{jobGrade}', [JobGradeController::class, 'show']);
    Route::put('/job-grades/{jobGrade}', [JobGradeController::class, 'update']);
    Route::delete('/job-grades/{jobGrade}', [JobGradeController::class, 'destroy']);

    // Job Level:
    Route::get('/job-levels-list', [JobLevelController::class, 'list']);
    Route::get('/job-levels', [JobLevelController::class, 'index']);
    Route::post('/job-levels', [JobLevelController::class, 'store']);
    Route::get('/job-levels/{jobLevel}', [JobLevelController::class, 'show']);
    Route::put('/job-levels/{jobLevel}', [JobLevelController::class, 'update']);
    Route::delete('/job-levels/{jobLevel}', [JobLevelController::class, 'destroy']);

    // ── Shift Master ──────────────────────────────────────────────────────────────
    Route::get('/shifts-list', [ShiftController::class, 'list']);
    Route::get('/shifts/export', [ShiftController::class, 'export']);           // ← BARU
    Route::get('/shifts/import-template', [ShiftController::class, 'importTemplate']);   // ← BARU
    Route::post('/shifts/import', [ShiftController::class, 'import']);            // ← BARU
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::get('/shifts/{shift}', [ShiftController::class, 'show']);
    Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
    Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);

    // ── Employee Shift (assignment) ───────────────────────────────────────────────
    Route::get('/employee-shifts', [EmployeeShiftController::class, 'index']);
    Route::post('/employee-shifts', [EmployeeShiftController::class, 'store']);
    Route::get('/employee-shifts/{employeeShift}', [EmployeeShiftController::class, 'show']);
    Route::put('/employee-shifts/{employeeShift}', [EmployeeShiftController::class, 'update']);
    Route::delete('/employee-shifts/{employeeShift}', [EmployeeShiftController::class, 'destroy']);
    Route::post('/employee-shifts/bulk', [EmployeeShiftController::class, 'bulkStore']);

    // Shift Weekly Pattern
    Route::get('/shift-patterns-list', [ShiftWeeklyPatternController::class, 'list']);
    Route::get('/shift-patterns', [ShiftWeeklyPatternController::class, 'index']);
    Route::post('/shift-patterns', [ShiftWeeklyPatternController::class, 'store']);
    Route::get('/shift-patterns/{pattern}', [ShiftWeeklyPatternController::class, 'show']);
    Route::put('/shift-patterns/{pattern}', [ShiftWeeklyPatternController::class, 'update']);
    Route::delete('/shift-patterns/{pattern}', [ShiftWeeklyPatternController::class, 'destroy']);

    // Employee Status
    Route::get('/employee-statuses-list', [EmployeeStatusController::class, 'list']);   // ← sebelum {id}
    Route::get('/employee-statuses', [EmployeeStatusController::class, 'index']);
    Route::post('/employee-statuses', [EmployeeStatusController::class, 'store']);
    Route::get('/employee-statuses/{employeeStatus}', [EmployeeStatusController::class, 'show']);
    Route::put('/employee-statuses/{employeeStatus}', [EmployeeStatusController::class, 'update']);
    Route::delete('/employee-statuses/{employeeStatus}', [EmployeeStatusController::class, 'destroy']);

    // Laporan Aktivitas
    Route::get('/laporan-aktivitas/summary', [LaporanAktivitasController::class, 'summary']);
    Route::get('/laporan-aktivitas/export', [LaporanAktivitasController::class, 'export']);
    Route::get('/tipe-aktivitas', [TipeAktivitasController::class, 'index']);

});

// ─────────────────────────────────────────────────────────────────────────────
// ROUTE LAMA YANG DIHAPUS:
//
// Route::get('/lokasi/users', [LokasiController::class, 'users']);
//   → ganti: GET /admin/employees-list
//
// Route::get('/lokasi/cek-duplikat', [LokasiController::class, 'cekDuplikat']);
//   → ganti: POST /admin/employee-lokasi/cek-duplikat
//
// Route::get('/lokasi', ...), Route::post('/lokasi', ...),
// Route::put('/lokasi/{id}', ...), Route::delete('/lokasi/{id}', ...)
//   → ganti: semua /admin/employee-lokasi di atas
//
// Route::get('/admin/users/all', [AdminAbsensiController::class, 'getAllUsers'])
//   → ganti: GET /admin/employees  (sekarang dari Employee, bukan User)
// ─────────────────────────────────────────────────────────────────────────────
