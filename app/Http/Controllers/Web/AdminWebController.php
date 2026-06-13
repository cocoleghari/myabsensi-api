<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePusatLokasi;
use App\Models\EmployeeShift;
use App\Models\EmployeeStatus;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\PermintaanAbsen;
use App\Models\Position;
use App\Models\PusatLokasi;
use App\Models\Shift;
use App\Models\ShiftWeeklyPattern;
use App\Models\User;

class AdminWebController extends Controller
{
    public function dashboard()
    {
        $totalKaryawan = Employee::count();
        $absensiHariIni = Absensi::whereDate('created_at', today())->count();
        $menungguPersetujuan = PermintaanAbsen::where('status', 'pending')->count();
        $totalUser = User::where('role', 'employee')->count();

        return view('admin.dashboard', compact('totalKaryawan', 'absensiHariIni', 'menungguPersetujuan', 'totalUser'));
    }

    public function listAkun()
    {
        $users = User::paginate(15);

        return view('admin.list-akun', compact('users'));
    }

    public function lokasiUser()
    {
        $data = EmployeePusatLokasi::with(['employee', 'pusatLokasi'])->paginate(15);

        return view('admin.lokasi-user', compact('data'));
    }

    public function pengaturanLokasi()
    {
        $lokasi = PusatLokasi::paginate(15);

        return view('admin.pengaturan-lokasi', compact('lokasi'));
    }

    public function department()
    {
        $departments = Department::with('manager')->paginate(15);

        return view('admin.department', compact('departments'));
    }

    public function karyawan()
    {
        $karyawan = Employee::with(['department', 'position', 'company'])->paginate(15);

        return view('admin.karyawan', compact('karyawan'));
    }

    public function posisi()
    {
        $posisi = Position::paginate(15);

        return view('admin.posisi', compact('posisi'));
    }

    public function jobGrade()
    {
        $jobGrades = JobGrade::paginate(15);

        return view('admin.job-grade', compact('jobGrades'));
    }

    public function jobLevel()
    {
        $jobLevels = JobLevel::paginate(15);

        return view('admin.job-level', compact('jobLevels'));
    }

    public function statusKaryawan()
    {
        $statuses = EmployeeStatus::paginate(15);

        return view('admin.status-karyawan', compact('statuses'));
    }

    public function masterShift()
    {
        $shifts = Shift::paginate(15);

        return view('admin.master-shift', compact('shifts'));
    }

    public function polaShift()
    {
        $pola = ShiftWeeklyPattern::paginate(15);

        return view('admin.pola-shift', compact('pola'));
    }

    public function assignShift()
    {
        $data = EmployeeShift::with(['employee', 'shift'])->paginate(15);

        return view('admin.assign-shift', compact('data'));
    }

    public function laporanAbsensi()
    {
        $absensi = Absensi::with(['employee', 'pusatLokasi'])->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.laporan-absensi', compact('absensi'));
    }

    public function laporanAktivitas()
    {
        $aktivitas = Aktivitas::with(['user'])->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.laporan-aktivitas', compact('aktivitas'));
    }

    public function riwayatUser()
    {
        $absensi = Absensi::with(['employee', 'pusatLokasi'])->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.riwayat-user', compact('absensi'));
    }
}
