<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePusatLokasi;
use App\Models\PusatLokasi;
use Illuminate\Http\Request;

class LokasiUserController extends Controller
{
    // ── Index: 1 row per karyawan ────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Employee::whereHas('pusatLokasis')
            ->with([
                'department:id,name',
                'position:id,name',
            ])
            ->withCount('pusatLokasis');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('pusat_lokasi_id')) {
            $query->whereHas('pusatLokasis', function ($q) use ($request) {
                $q->where('pusat_lokasis.id', $request->pusat_lokasi_id);
            });
        }

        $employees = $query->orderBy('full_name')->paginate(20)->withQueryString();
        $lokasis = PusatLokasi::select('id', 'nama_lokasi')->orderBy('nama_lokasi')->get();
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        // Hitung karyawan yang BELUM memiliki lokasi absensi sama sekali
        $employeesWithoutLocation = Employee::whereDoesntHave('pusatLokasis')->count();

        return view('admin.lokasi-user', compact('employees', 'lokasis', 'departments', 'employeesWithoutLocation'));
    }

    // ── Detail lokasi satu karyawan (JSON untuk modal) ───────────────────────
    public function detail(Employee $employee)
    {
        $lokasis = EmployeePusatLokasi::where('employee_id', $employee->id)
            ->with('pusatLokasi:id,nama_lokasi,titik_kordinat')
            ->select('id', 'employee_id', 'pusat_lokasi_id', 'radius_meter', 'keterangan')
            ->get()
            ->map(fn ($r) => [
                'pivot_id' => $r->id,
                'nama_lokasi' => $r->pusatLokasi?->nama_lokasi ?? '-',
                'koordinat' => $r->pusatLokasi?->titik_kordinat ?? '-',
                'radius_meter' => $r->radius_meter,
                'keterangan' => $r->keterangan,
            ]);

        return response()->json([
            'employee_name' => $employee->full_name,
            'data' => $lokasis,
        ]);
    }

    // ── Tambah satu lokasi (legacy, bisa dihapus) ───────────────────────────
    public function store(Request $request)
    {
        return $this->storeMultiple($request);
    }

    // ── Tambah banyak lokasi sekaligus ───────────────────────────────────────
    public function storeMultiple(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'pusat_lokasi_ids' => 'required|array|min:1',
            'pusat_lokasi_ids.*' => 'exists:pusat_lokasis,id',
        ]);

        $employeeId = $request->employee_id;
        $now = now();
        $added = 0;
        $skipped = 0;

        foreach ($request->pusat_lokasi_ids as $lokasiId) {
            $exists = EmployeePusatLokasi::where('employee_id', $employeeId)
                ->where('pusat_lokasi_id', $lokasiId)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            EmployeePusatLokasi::create([
                'employee_id' => $employeeId,
                'pusat_lokasi_id' => $lokasiId,
                'radius_meter' => 100,
                'keterangan' => null,
            ]);
            $added++;
        }

        $msg = $added.' lokasi berhasil ditambahkan.';
        if ($skipped > 0) {
            $msg .= ' '.$skipped.' lokasi dilewati (sudah terdaftar).';
        }

        return back()->with('success', $msg);
    }

    // ── Hapus satu relasi pivot ───────────────────────────────────────────────
    public function destroyPivot(EmployeePusatLokasi $pivot)
    {
        $pivot->delete();

        return back()->with('success', 'Relasi lokasi berhasil dihapus.');
    }

    // ── Hapus semua relasi satu karyawan ─────────────────────────────────────
    public function destroyAll(Employee $employee)
    {
        EmployeePusatLokasi::where('employee_id', $employee->id)->delete();

        return back()->with('success', 'Semua lokasi karyawan berhasil dihapus.');
    }
}
