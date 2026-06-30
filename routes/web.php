<?php

use App\Http\Controllers\Mobile\LaporanAbsensiController as MobileLaporanAbsensiController;
use App\Http\Controllers\Mobile\LaporanAktivitasController as MobileLaporanAktivitasController;
use App\Http\Controllers\Mobile\ShiftController as MobileShiftController;
use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\AssignShiftController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DepartmentController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\EmployeeStatusController;
use App\Http\Controllers\Web\FotoWajahController;
use App\Http\Controllers\Web\JobGradeController;
use App\Http\Controllers\Web\JobLevelController;
use App\Http\Controllers\Web\LaporanAbsensiController;
use App\Http\Controllers\Web\LaporanAktivitasController;
use App\Http\Controllers\Web\LokasiUserController;
use App\Http\Controllers\Web\PositionController;
use App\Http\Controllers\Web\PusatLokasiController;
use App\Http\Controllers\Web\ShiftController;
use App\Http\Controllers\Web\ShiftWeeklyPatternController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/admin/login', [AuthWebController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthWebController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthWebController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth', 'web.access'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');

    // Pengaturan Akun
    Route::resource('list-akun', UserController::class)
        ->except(['show'])
        ->parameters(['list-akun' => 'user']);

    // Lokasi User (per karyawan)
    Route::get('/lokasi-user', [LokasiUserController::class, 'index'])->name('lokasi-user');
    Route::post('/lokasi-user', [LokasiUserController::class, 'store'])->name('lokasi-user.store');
    Route::post('/lokasi-user/store-multiple', [LokasiUserController::class, 'storeMultiple'])->name('lokasi-user.store-multiple');
    Route::get('/lokasi-user/{employee}/detail', [LokasiUserController::class, 'detail'])->name('lokasi-user.detail');
    Route::delete('/lokasi-user/{employee}/delete-all', [LokasiUserController::class, 'destroyAll'])->name('lokasi-user.destroy-all');
    Route::delete('/lokasi-user/pivot/{pivot}', [LokasiUserController::class, 'destroyPivot'])->name('lokasi-user.destroy-pivot');

    // Pusat Lokasi
    Route::resource('pengaturan-lokasi', PusatLokasiController::class)
        ->except(['show'])
        ->parameters(['pengaturan-lokasi' => 'lokasi']);
    Route::get('/pengaturan-lokasi/{lokasi}/assign', [PusatLokasiController::class, 'assignForm'])->name('pengaturan-lokasi.assign');
    Route::get('/pengaturan-lokasi/{lokasi}/assign/search', [PusatLokasiController::class, 'assignSearch'])->name('pengaturan-lokasi.assign.search');
    Route::get('/pengaturan-lokasi/{lokasi}/assign/all-ids', [PusatLokasiController::class, 'assignAllIds'])->name('pengaturan-lokasi.assign.all-ids');
    Route::post('/pengaturan-lokasi/{lokasi}/assign', [PusatLokasiController::class, 'assignStore'])->name('pengaturan-lokasi.assign.store');
    Route::get('/pengaturan-lokasi/{lokasi}/employees', [PusatLokasiController::class, 'employees'])->name('pengaturan-lokasi.employees');

    // Department
    Route::get('/department', [DepartmentController::class, 'index'])->name('department.index');
    Route::get('/department/tree', [DepartmentController::class, 'tree'])->name('department.tree');
    Route::get('/department/tree-html', [DepartmentController::class, 'treeHtml'])->name('department.tree-html');
    Route::get('/department/{department}/employees-html', [DepartmentController::class, 'employeesHtml'])->name('department.employees-html');
    Route::get('/department/create', [DepartmentController::class, 'create'])->name('department.create');
    Route::post('/department', [DepartmentController::class, 'store'])->name('department.store');
    Route::get('/department/{department}/edit', [DepartmentController::class, 'edit'])->name('department.edit');
    Route::put('/department/{department}', [DepartmentController::class, 'update'])->name('department.update');
    Route::delete('/department/{department}', [DepartmentController::class, 'destroy'])->name('department.destroy');

    Route::get('/department/export', [DepartmentController::class, 'export'])->name('department.export');
    Route::get('/department/import-template', [DepartmentController::class, 'importTemplate'])->name('department.import-template');
    Route::post('/department/import', [DepartmentController::class, 'import'])->name('department.import');

    // Pengaturan Karyawan
    Route::resource('karyawan', EmployeeController::class);
    Route::get('/karyawan/{karyawan}', [EmployeeController::class, 'show'])->name('karyawan.show');

    // Foto Dasar
    Route::get('/foto-wajah', [FotoWajahController::class, 'index'])->name('foto-wajah.index');
    Route::post('/foto-wajah/{karyawan}/upload', [FotoWajahController::class, 'upload'])->name('foto-wajah.upload');
    Route::post('/foto-wajah/{karyawan}/reset', [FotoWajahController::class, 'reset'])->name('foto-wajah.reset');

    // Posisi / Jabatan
    Route::get('/posisi', [PositionController::class, 'index'])->name('posisi.index');
    Route::get('/posisi/create', [PositionController::class, 'create'])->name('posisi.create');
    Route::post('/posisi', [PositionController::class, 'store'])->name('posisi.store');
    Route::get('/posisi/{posisi}/edit', [PositionController::class, 'edit'])->name('posisi.edit');
    Route::put('/posisi/{posisi}', [PositionController::class, 'update'])->name('posisi.update');
    Route::delete('/posisi/{posisi}', [PositionController::class, 'destroy'])->name('posisi.destroy');

    Route::get('/posisi/export', [PositionController::class, 'export'])->name('posisi.export');
    Route::get('/posisi/import-template', [PositionController::class, 'importTemplate'])->name('posisi.import-template');
    Route::post('/posisi/import', [PositionController::class, 'import'])->name('posisi.import');

    // Job Grade
    Route::get('/job-grade', [JobGradeController::class, 'index'])->name('job-grade.index');
    Route::get('/job-grade/create', [JobGradeController::class, 'create'])->name('job-grade.create');
    Route::post('/job-grade', [JobGradeController::class, 'store'])->name('job-grade.store');
    Route::get('/job-grade/{jobGrade}/edit', [JobGradeController::class, 'edit'])->name('job-grade.edit');
    Route::put('/job-grade/{jobGrade}', [JobGradeController::class, 'update'])->name('job-grade.update');
    Route::delete('/job-grade/{jobGrade}', [JobGradeController::class, 'destroy'])->name('job-grade.destroy');

    Route::get('/job-grade/export', [JobGradeController::class, 'export'])->name('job-grade.export');
    Route::get('/job-grade/import-template', [JobGradeController::class, 'importTemplate'])->name('job-grade.import-template');
    Route::post('/job-grade/import', [JobGradeController::class, 'import'])->name('job-grade.import');

    // Job Level
    Route::resource('job-level', JobLevelController::class)
        ->except(['show'])
        ->parameters(['job-level' => 'jobLevel']);

    // Status Karyawan
    Route::resource('status-karyawan', EmployeeStatusController::class)
        ->except(['show'])
        ->parameters(['status-karyawan' => 'employeeStatus']);

    // Master Shift
    Route::resource('master-shift', ShiftController::class)
        ->except(['show'])
        ->parameters(['master-shift' => 'shift']);
    Route::get('/master-shift/export', [MobileShiftController::class, 'export'])->name('master-shift.export');
    Route::get('/master-shift/import-template', [MobileShiftController::class, 'importTemplate'])->name('master-shift.import-template');
    Route::post('/master-shift/import', [MobileShiftController::class, 'import'])->name('master-shift.import');

    // Pola Shift Mingguan
    Route::resource('pola-shift', ShiftWeeklyPatternController::class)
        ->except(['show'])
        ->parameters(['pola-shift' => 'pattern']);

    // Assign Shift
    Route::get('/assign-shift', [AssignShiftController::class, 'index'])->name('assign-shift.index');
    Route::get('/assign-shift/create', [AssignShiftController::class, 'create'])->name('assign-shift.create');
    Route::post('/assign-shift', [AssignShiftController::class, 'store'])->name('assign-shift.store');
    Route::get('/assign-shift/{assignShift}/edit', [AssignShiftController::class, 'edit'])->name('assign-shift.edit');
    Route::put('/assign-shift/{assignShift}', [AssignShiftController::class, 'update'])->name('assign-shift.update');
    Route::delete('/assign-shift/{assignShift}', [AssignShiftController::class, 'destroy'])->name('assign-shift.destroy');

    // AJAX endpoints
    Route::get('/assign-shift/search-employees', [AssignShiftController::class, 'searchEmployees'])->name('assign-shift.search');
    Route::get('/assign-shift/all-employee-ids', [AssignShiftController::class, 'allEmployeeIds'])->name('assign-shift.all-ids');

    // Laporan Absensi
    Route::get('/laporan-absensi', [LaporanAbsensiController::class, 'index'])->name('laporan-absensi');
    Route::get('/laporan-absensi/export', [MobileLaporanAbsensiController::class, 'export'])->name('laporan-absensi.export');

    // Laporan Aktivitas
    Route::get('/laporan-aktivitas', [LaporanAktivitasController::class, 'index'])->name('laporan-aktivitas');
    Route::get('/laporan-aktivitas/export', [MobileLaporanAktivitasController::class, 'export'])->name('laporan-aktivitas.export');

    Route::get('/riwayat-user', [AdminWebController::class, 'riwayatUser'])->name('riwayat-user');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});
