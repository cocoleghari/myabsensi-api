<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\EmployeeStatus;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\PermintaanAbsen;
use App\Models\Position;
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

    public function department()
    {
        $departments = Department::with('manager')->paginate(15);

        return view('admin.department', compact('departments'));
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
}
