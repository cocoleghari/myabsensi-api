<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Shift;
use App\Models\ShiftWeeklyPattern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssignShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeShift::with([
            'employee:id,full_name,employee_code,nik,department_id,position_id',
            'employee.department:id,name',
            'employee.position:id,name',
            'shift:id,nama,kode,jam_masuk,jam_pulang',
            'pattern:id,nama,kode',
        ])->whereHas('employee');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->join('employees', 'employee_shifts.employee_id', '=', 'employees.id')
                ->where(function ($q) use ($search) {
                    $q->where('employees.full_name', 'like', "%{$search}%")
                        ->orWhere('employees.employee_code', 'like', "%{$search}%")
                        ->orWhere('employees.nik', 'like', "%{$search}%");
                })
                ->select('employee_shifts.*');
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('aktif')) {
            if ($request->aktif === '1') {
                $query->where(function ($q) {
                    $q->whereNull('tanggal_selesai')
                        ->orWhere('tanggal_selesai', '>=', now()->toDateString());
                });
            } else {
                $query->whereNotNull('tanggal_selesai')
                    ->where('tanggal_selesai', '<', now()->toDateString());
            }
        }

        $assignments = $query->orderByDesc('tanggal_mulai')->paginate(20)->withQueryString();
        $shifts = Shift::select('id', 'nama', 'kode')->orderBy('nama')->get();

        // Hitung karyawan yang TIDAK punya assignment shift aktif sama sekali
        $employeesWithoutShift = Employee::whereDoesntHave('shifts', function ($q) {
            $q->where(function ($sub) {
                $sub->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            });
        })->count();

        return view('admin.assign-shift', compact('assignments', 'shifts', 'employeesWithoutShift'));
    }

    public function create()
    {
        $shifts = Shift::select('id', 'nama', 'kode', 'jam_masuk', 'jam_pulang')->orderBy('nama')->get();
        $patterns = ShiftWeeklyPattern::select('id', 'nama', 'kode')->orderBy('nama')->get();
        $options = $this->getFilterOptions();

        return view('admin.assign-shift-form', compact('shifts', 'patterns') + $options);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'shift_id' => ['nullable', 'integer', Rule::exists('shifts', 'id')->whereNull('deleted_at')],
            'pattern_id' => ['nullable', 'integer', Rule::exists('shift_weekly_patterns', 'id')->whereNull('deleted_at')],
            'tanggal_mulai' => 'required|date|date_format:Y-m-d',
            'tanggal_selesai' => 'nullable|date|date_format:Y-m-d|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if (! empty($data['shift_id']) && ! empty($data['pattern_id'])) {
            return back()->withErrors(['shift_id' => 'Pilih salah satu: Shift Kerja atau Pola Mingguan, tidak bisa keduanya.'])->withInput();
        }

        $inserted = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, &$inserted, &$skipped) {
            foreach ($data['employee_ids'] as $employeeId) {
                // Assignment dengan tanggal mulai SAMA PERSIS → hapus (akan digantikan yang baru)
                EmployeeShift::where('employee_id', $employeeId)
                    ->where('tanggal_mulai', $data['tanggal_mulai'])
                    ->delete();

                // Assignment lama yang masih aktif dan mulai SEBELUM tanggal baru → tutup
                $sebelumnya = EmployeeShift::where('employee_id', $employeeId)
                    ->where(function ($q) use ($data) {
                        $q->whereNull('tanggal_selesai')
                            ->orWhere('tanggal_selesai', '>=', $data['tanggal_mulai']);
                    })
                    ->where('tanggal_mulai', '<', $data['tanggal_mulai'])
                    ->get();

                foreach ($sebelumnya as $lama) {
                    $lama->update([
                        'tanggal_selesai' => date('Y-m-d', strtotime($data['tanggal_mulai'].' -1 day')),
                    ]);
                }

                EmployeeShift::create([
                    'employee_id' => $employeeId,
                    'shift_id' => $data['shift_id'] ?? null,
                    'pattern_id' => $data['pattern_id'] ?? null,
                    'tanggal_mulai' => $data['tanggal_mulai'],
                    'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
                    'keterangan' => $data['keterangan'] ?? null,
                ]);
                $inserted++;
            }
        });

        return redirect()->route('admin.assign-shift.index')
            ->with('success', "{$inserted} karyawan berhasil di-assign shift.");
    }

    public function edit(EmployeeShift $assignShift)
    {
        $assignShift->load(['employee:id,full_name,employee_code,nik,department_id,position_id', 'employee.department:id,name', 'employee.position:id,name', 'shift', 'pattern']);
        $shifts = Shift::select('id', 'nama', 'kode', 'jam_masuk', 'jam_pulang')->orderBy('nama')->get();
        $patterns = ShiftWeeklyPattern::select('id', 'nama', 'kode')->orderBy('nama')->get();

        return view('admin.assign-shift-edit', compact('assignShift', 'shifts', 'patterns'));
    }

    public function update(Request $request, EmployeeShift $assignShift)
    {
        $data = $request->validate([
            'shift_id' => ['nullable', 'integer', Rule::exists('shifts', 'id')->whereNull('deleted_at')],
            'pattern_id' => ['nullable', 'integer', Rule::exists('shift_weekly_patterns', 'id')->whereNull('deleted_at')],
            'tanggal_mulai' => 'required|date|date_format:Y-m-d',
            'tanggal_selesai' => 'nullable|date|date_format:Y-m-d|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $assignShift->update($data);

        return redirect()->route('admin.assign-shift.index')
            ->with('success', 'Assignment shift berhasil diperbarui.');
    }

    public function destroy(EmployeeShift $assignShift)
    {
        $assignShift->delete();

        return redirect()->route('admin.assign-shift.index')
            ->with('success', 'Assignment shift berhasil dihapus.');
    }

    // Endpoint AJAX — search karyawan untuk form assign
    public function searchEmployees(Request $request)
    {
        $query = Employee::select('id', 'full_name', 'employee_code', 'nik', 'department_id', 'position_id', 'job_level_id', 'job_grade_id')
            ->with('department:id,name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('nik', 'like', "%{$s}%");
            });
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

        $result = $query->orderBy('full_name')->paginate(20);

        return response()->json([
            'data' => $result->map(fn ($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'nik' => $e->nik,
                'department_name' => $e->department?->name ?? '-',
            ]),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'total' => $result->total(),
        ]);
    }

    public function allEmployeeIds(Request $request)
    {
        $query = Employee::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('nik', 'like', "%{$s}%");
            });
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

    private function getFilterOptions(): array
    {
        return [
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'positions' => Position::select('id', 'name')->orderBy('name')->get(),
            'jobLevels' => JobLevel::select('id', 'name')->orderBy('name')->get(),
            'jobGrades' => JobGrade::select('id', 'name', 'code')->orderBy('name')->get(),
        ];
    }
}
