<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with([
            'company:id,name',
            'department:id,name',
            'position:id,name',
            'jobLevel:id,name',
            'jobGrade:id,name,code',
            'status:id,label',
            'user:id,email,role',
        ])->select([
            'id', 'employee_code', 'full_name', 'nickname',
            'company_id', 'department_id', 'position_id',
            'job_level_id', 'job_grade_id', 'user_id',
            'employee_status_id', 'employment_type', 'photo_url',
            'nik', 'phone', 'join_date', 'resign_date',
        ]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', '%'.$request->search.'%')
                    ->orWhere('nik', 'like', '%'.$request->search.'%')
                    ->orWhere('employee_code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->whereNull('resign_date');
            } elseif ($request->status === 'nonaktif') {
                $query->whereNotNull('resign_date');
            }
        }

        if ($request->filled('without_shift') && $request->without_shift === '1') {
            $query->whereDoesntHave('shifts', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('tanggal_selesai')
                        ->orWhere('tanggal_selesai', '>=', now()->toDateString());
                });
            });
        }

        if ($request->filled('without_lokasi') && $request->without_lokasi === '1') {
            $query->whereDoesntHave('pusatLokasis');
        }

        $karyawan = $query->orderBy('full_name')->paginate(15)->withQueryString();
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return view('admin.karyawan', compact('karyawan', 'departments'));
    }

    public function create()
    {
        $options = $this->getOptions();

        return view('admin.karyawan-form', $options);
    }

    public function show(Employee $karyawan)
    {
        $karyawan->load([
            'company:id,name',
            'department:id,name',
            'position:id,name',
            'jobLevel:id,name',
            'jobGrade:id,name,code',
            'status:id,label',
            'user:id,email,role',
        ]);

        return view('admin.karyawan-detail', compact('karyawan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_level_id' => 'nullable|exists:job_levels,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'employee_code' => 'nullable|string|unique:employees,employee_code',
            'nik' => 'required|string|unique:employees,nik',
            'ktp_number' => 'nullable|string|unique:employees,ktp_number',
            'full_name' => 'required|string|max:200',
            'nickname' => 'nullable|string|max:100|unique:employees,nickname',
            'gender' => ['required', Rule::in(['male', 'female'])],
            'place_of_birth' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'religion' => 'nullable|string',
            'blood_type' => 'nullable|string|max:3',
            'phone' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'emergency_contact_relation' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string|max:10',
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'intern', 'freelance', 'evaluation'])],
            'join_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'employee_status_id' => 'nullable|exists:employee_statuses,id',
            'npwp' => 'nullable|string|unique:employees,npwp',
            'bpjs_kesehatan' => 'nullable|string|unique:employees,bpjs_kesehatan',
            'bpjs_ketenagakerjaan' => 'nullable|string|unique:employees,bpjs_ketenagakerjaan',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'last_education' => ['nullable', Rule::in(['sd', 'smp', 'sma', 'd1', 'd2', 'd3', 'd4', 's1', 's2', 's3'])],
            'last_education_major' => 'nullable|string',
            'last_education_institution' => 'nullable|string',
            // Akun user
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->whereNotNull('email')],
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|in:employee,admin,hrd,manager',
        ]);

        try {
            DB::transaction(function () use ($data, $request) {
                // Buat akun user otomatis
                $userData = [
                    'name' => $data['full_name'],
                    'password' => bcrypt($data['password'] ?? str()->random(12)),
                    'role' => $data['role'] ?? 'employee',
                    'is_active' => $request->boolean('is_active'),
                ];
                if (! empty($data['email'])) {
                    $userData['email'] = $data['email'];
                }
                $user = \App\Models\User::create($userData);
                $data['user_id'] = $user->id;

                unset($data['email'], $data['password'], $data['role']);

                foreach (['full_name', 'nickname', 'employee_code'] as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = $this->cleanString($data[$field]);
                    }
                }

                Employee::create($data);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withInput()->with('error', 'Data duplikat terdeteksi. Periksa kembali NIK, nickname, atau data unik lainnya.');
            }
            throw $e;
        }

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function edit(Employee $karyawan)
    {
        $options = $this->getOptions();

        return view('admin.karyawan-form', array_merge($options, compact('karyawan')));
    }

    public function update(Request $request, Employee $karyawan)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_level_id' => 'nullable|exists:job_levels,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'employee_code' => ['nullable', 'string', Rule::unique('employees', 'employee_code')->ignore($karyawan->id)],
            'nik' => ['required', 'string', Rule::unique('employees', 'nik')->ignore($karyawan->id)],
            'ktp_number' => ['nullable', 'string', Rule::unique('employees', 'ktp_number')->ignore($karyawan->id)],
            'full_name' => 'required|string|max:200',
            'nickname' => ['nullable', 'string', 'max:100', Rule::unique('employees', 'nickname')->ignore($karyawan->id)],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'place_of_birth' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'religion' => 'nullable|string',
            'blood_type' => 'nullable|string|max:3',
            'phone' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'emergency_contact_relation' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string|max:10',
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'intern', 'freelance', 'evaluation'])],
            'join_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'resign_date' => 'nullable|date',
            'employee_status_id' => 'nullable|exists:employee_statuses,id',
            'npwp' => ['nullable', 'string', Rule::unique('employees', 'npwp')->ignore($karyawan->id)],
            'bpjs_kesehatan' => ['nullable', 'string', Rule::unique('employees', 'bpjs_kesehatan')->ignore($karyawan->id)],
            'bpjs_ketenagakerjaan' => ['nullable', 'string', Rule::unique('employees', 'bpjs_ketenagakerjaan')->ignore($karyawan->id)],
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'last_education' => ['nullable', Rule::in(['sd', 'smp', 'sma', 'd1', 'd2', 'd3', 'd4', 's1', 's2', 's3'])],
            'last_education_major' => 'nullable|string',
            'last_education_institution' => 'nullable|string',
            // Akun user
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->whereNotNull('email')->ignore($karyawan->user_id)],
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|in:employee,admin,hrd,manager',
        ]);

        try {
            DB::transaction(function () use ($data, $karyawan, $request) {
                // Update akun user
                if ($karyawan->user_id) {
                    $user = \App\Models\User::findOrFail($karyawan->user_id);
                    if (! empty($data['email'])) {
                        $user->email = $data['email'];
                    }
                    if (! empty($data['password'])) {
                        $user->password = bcrypt($data['password']);
                    }
                    if (! empty($data['role'])) {
                        $user->role = $data['role'];
                    }

                    $user->is_active = $request->boolean('is_active');
                    $user->save();
                }

                unset($data['email'], $data['password'], $data['role']);

                foreach (['full_name', 'nickname', 'employee_code'] as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = $this->cleanString($data[$field]);
                    }
                }

                $karyawan->update($data);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->withInput()->with('error', 'Data duplikat terdeteksi. Periksa kembali NIK, nickname, atau data unik lainnya.');
            }
            throw $e;
        }

        return redirect()->route('admin.karyawan.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $karyawan)
    {
        if ($karyawan->user && $karyawan->user->is_active) {
            return redirect()->route('admin.karyawan.index')
                ->with('error', 'Tidak dapat menghapus karyawan yang akunnya masih aktif.');
        }

        $karyawan->delete();

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getOptions(): array
    {
        return [
            'companies' => Company::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'positions' => Position::select('id', 'name')->orderBy('name')->get(),
            'jobLevels' => JobLevel::select('id', 'name')->orderBy('name')->get(),
            'jobGrades' => JobGrade::select('id', 'name', 'code')->orderBy('name')->get(),
            'employeeStatuses' => EmployeeStatus::select('id', 'label')
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get(),
        ];
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return $value;
    }
}
