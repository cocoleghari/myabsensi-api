<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePusatLokasi;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\PusatLokasi;
use Illuminate\Http\Request;

class PusatLokasiController extends Controller
{
    public function index(Request $request)
    {
        $query = PusatLokasi::with('company:id,name')->withCount('employees');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_lokasi', 'like', '%'.$request->search.'%')
                    ->orWhere('keterangan', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($request->status === 'nonaktif') {
                $query->where('is_active', false);
            }
        }

        $lokasi = $query->orderBy('nama_lokasi')->paginate(15)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.pengaturan-lokasi', compact('lokasi', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.pengaturan-lokasi-form', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'nama_lokasi' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        PusatLokasi::create([
            'company_id' => $data['company_id'],
            'nama_lokasi' => $data['nama_lokasi'],
            'titik_kordinat' => $data['latitude'].','.$data['longitude'],
            'keterangan' => $data['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.pengaturan-lokasi.index')->with('success', 'Pusat lokasi berhasil ditambahkan.');
    }

    public function edit(PusatLokasi $lokasi)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $coords = array_pad(explode(',', $lokasi->titik_kordinat), 2, null);
        $latitude = trim($coords[0] ?? '');
        $longitude = trim($coords[1] ?? '');

        return view('admin.pengaturan-lokasi-form', compact('companies', 'lokasi', 'latitude', 'longitude'));
    }

    public function update(Request $request, PusatLokasi $lokasi)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'nama_lokasi' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'keterangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $lokasi->update([
            'company_id' => $data['company_id'],
            'nama_lokasi' => $data['nama_lokasi'],
            'titik_kordinat' => $data['latitude'].','.$data['longitude'],
            'keterangan' => $data['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.pengaturan-lokasi.index')->with('success', 'Pusat lokasi berhasil diperbarui.');
    }

    public function destroy(PusatLokasi $lokasi)
    {
        $jumlahKaryawan = $lokasi->employees()->count();

        if ($jumlahKaryawan > 0) {
            return redirect()->route('admin.pengaturan-lokasi.index')
                ->with('error', "Tidak dapat menghapus lokasi karena masih digunakan oleh {$jumlahKaryawan} karyawan.");
        }

        $lokasi->delete();

        return redirect()->route('admin.pengaturan-lokasi.index')->with('success', 'Pusat lokasi berhasil dihapus.');
    }

    public function assignForm(PusatLokasi $lokasi)
    {
        $assignedIds = $lokasi->employees()->pluck('employees.id')->toArray();
        $departments = Department::select('id', 'name')->orderBy('name')->get();
        $positions = Position::select('id', 'name')->orderBy('name')->get();
        $jobLevels = JobLevel::select('id', 'name')->orderBy('name')->get();
        $jobGrades = JobGrade::select('id', 'name', 'code')->orderBy('name')->get();

        return view('admin.pengaturan-lokasi-assign', compact(
            'lokasi', 'assignedIds', 'departments', 'positions', 'jobLevels', 'jobGrades'
        ));
    }

    public function assignSearch(Request $request, PusatLokasi $lokasi)
    {
        $query = Employee::select('id', 'full_name', 'employee_code', 'department_id', 'position_id')
            ->with(['department:id,name', 'position:id,name']);

        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('department_id')) {
            $query->whereIn('department_id', (array) $request->department_id);
        }

        if ($request->filled('position_id')) {
            $query->whereIn('position_id', (array) $request->position_id);
        }

        if ($request->filled('job_level_id')) {
            $query->whereIn('job_level_id', (array) $request->job_level_id);
        }

        if ($request->filled('job_grade_id')) {
            $query->whereIn('job_grade_id', (array) $request->job_grade_id);
        }

        $employees = $query->orderBy('full_name')->paginate(25);

        return response()->json([
            'data' => collect($employees->items())->map(fn ($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'employee_code' => $e->employee_code,
                'department_name' => $e->department?->name ?? '-',
                'position_name' => $e->position?->name ?? '-',
            ]),
            'current_page' => $employees->currentPage(),
            'last_page' => $employees->lastPage(),
            'total' => $employees->total(),
        ]);
    }

    public function assignAllIds(Request $request, PusatLokasi $lokasi)
    {
        $query = Employee::query();

        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('department_id')) {
            $query->whereIn('department_id', (array) $request->department_id);
        }

        if ($request->filled('position_id')) {
            $query->whereIn('position_id', (array) $request->position_id);
        }

        if ($request->filled('job_level_id')) {
            $query->whereIn('job_level_id', (array) $request->job_level_id);
        }

        if ($request->filled('job_grade_id')) {
            $query->whereIn('job_grade_id', (array) $request->job_grade_id);
        }

        return response()->json(['ids' => $query->pluck('id')]);
    }

    public function assignStore(Request $request, PusatLokasi $lokasi)
    {
        $data = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employees,id',
            'radius_meter' => 'nullable|integer|min:10|max:50000',
            'keterangan' => 'nullable|string|max:255',
            'overwrite' => 'nullable|boolean',
        ]);

        $radiusMeter = $data['radius_meter'] ?? 100;
        $keterangan = $data['keterangan'] ?? null;
        $overwrite = $request->boolean('overwrite', false);
        $employeeIds = array_unique($data['employee_ids']);

        if ($overwrite) {
            EmployeePusatLokasi::where('pusat_lokasi_id', $lokasi->id)->delete();
        }

        $now = now();
        $rows = [];

        foreach ($employeeIds as $employeeId) {
            if (! $overwrite) {
                $exists = EmployeePusatLokasi::where('pusat_lokasi_id', $lokasi->id)
                    ->where('employee_id', $employeeId)
                    ->exists();
                if ($exists) {
                    continue;
                }
            }

            $rows[] = [
                'pusat_lokasi_id' => $lokasi->id,
                'employee_id' => $employeeId,
                'radius_meter' => $radiusMeter,
                'keterangan' => $keterangan,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            EmployeePusatLokasi::insert($rows);
        }

        $newlyInserted = count($rows);
        $skipped = count($employeeIds) - $newlyInserted;

        $message = "{$newlyInserted} karyawan berhasil di-assign ke {$lokasi->nama_lokasi}";
        if ($skipped > 0) {
            $message .= ", {$skipped} dilewati (sudah terdaftar)";
        }

        return redirect()->route('admin.pengaturan-lokasi.index')->with('success', $message);
    }

    public function employees(PusatLokasi $lokasi)
    {
        $employees = $lokasi->employees()
            ->select('employees.id', 'employees.full_name', 'employees.position_id', 'employees.department_id')
            ->with(['position:id,name', 'department:id,name'])
            ->orderBy('employees.full_name')
            ->get();

        return response()->json([
            'nama_lokasi' => $lokasi->nama_lokasi,
            'data' => $employees->map(fn ($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'position_name' => $e->position?->name ?? '-',
                'department_name' => $e->department?->name ?? '-',
            ]),
        ]);
    }
}
