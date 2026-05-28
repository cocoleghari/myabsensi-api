<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // GET /admin/employees
    public function index(Request $request)
    {
        $query = Employee::with([
            'company:id,name',
            'department:id,name',
            'position:id,name',
            'jobLevel:id,name',
            'jobGrade:id,name,code',
            'status:id,label',
        ])->select([
            'id', 'employee_code', 'full_name', 'nickname',
            'company_id', 'department_id', 'position_id',
            'job_level_id', 'job_grade_id',
            'employee_status_id', 'employment_type', 'photo_url', 'nik',
        ]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                    ->orWhere('nik', 'like', "%$search%")
                    ->orWhere('employee_code', 'like', "%$search%");
            });
        }

        if ($companyId = $request->query('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($jobLevelId = $request->query('job_level_id')) {
            $query->where('job_level_id', $jobLevelId);
        }

        if ($jobGradeId = $request->query('job_grade_id')) {
            $query->where('job_grade_id', $jobGradeId);
        }

        if ($employmentType = $request->query('employment_type')) {
            $query->where('employment_type', $employmentType);
        }

        $perPage = min((int) ($request->query('per_page', 10)), 1000);
        $employees = $query->orderBy('full_name')->paginate($perPage);

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'total' => $employees->total(),
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
            ],
        ]);
    }

    // POST /admin/employees
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_level_id' => 'nullable|exists:job_levels,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'employee_code' => 'nullable|string|unique:employees,employee_code',
            'nik' => 'nullable|string|unique:employees,nik',
            'ktp_number' => 'nullable|string',
            'full_name' => 'required|string|max:200',
            'nickname' => 'nullable|string|max:100',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'place_of_birth' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'religion' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'join_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'employee_status_id' => 'nullable|exists:employee_statuses,id',
            'npwp' => 'nullable|string',
            'bpjs_kesehatan' => 'nullable|string',
            'bpjs_ketenagakerjaan' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'last_education' => 'nullable|string',
            'last_education_major' => 'nullable|string',
            'last_education_institution' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'emergency_contact_relation' => 'nullable|string',

            'create_account' => 'nullable|boolean',
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->whereNotNull('email')],
            'password' => 'required_if:create_account,true|string|min:6',
            'role' => 'nullable|in:employee,admin,hrd,manager',
        ]);

        $userData = [
            'name' => $data['full_name'],
            'password' => bcrypt($data['password'] ?? str()->random(12)),
            'role' => $data['role'] ?? 'employee',
        ];
        if (! empty($data['email'])) {
            $userData['email'] = $data['email'];
        }

        $user = \App\Models\User::create($userData);
        $data['user_id'] = $user->id;

        unset($data['create_account'], $data['email'], $data['password'], $data['role']);

        if (array_key_exists('user_id', $data) && $data['user_id'] === null) {
            unset($data['user_id']);
        }

        foreach (['full_name', 'nickname', 'employee_code'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->cleanString($data[$field]);
            }
        }

        $employee = Employee::create($data);
        $employee->load(['company', 'department', 'position', 'jobLevel', 'jobGrade', 'status']);

        return response()->json([
            'message' => 'Karyawan berhasil ditambahkan',
            'data' => $employee,
        ], 201);
    }

    // GET /admin/employees/{id}
    public function show($id)
    {
        $employee = Employee::with([
            'user', 'company', 'department', 'position',
            'jobLevel', 'jobGrade', 'status', 'pusatLokasis',
        ])->findOrFail($id);

        return response()->json(['data' => $employee]);
    }

    // PUT /admin/employees/{id}
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'job_level_id' => 'nullable|exists:job_levels,id',
            'job_grade_id' => 'nullable|exists:job_grades,id',
            'employee_code' => ['nullable', 'string', Rule::unique('employees', 'employee_code')->ignore($id)],
            'nik' => ['nullable', 'string', Rule::unique('employees', 'nik')->ignore($id)],
            'ktp_number' => 'nullable|string',
            'full_name' => 'required|string|max:200',
            'nickname' => 'nullable|string|max:100',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'place_of_birth' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string',
            'religion' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'join_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'resign_date' => 'nullable|date',
            'employee_status_id' => 'nullable|exists:employee_statuses,id',
            'npwp' => 'nullable|string',
            'bpjs_kesehatan' => 'nullable|string',
            'bpjs_ketenagakerjaan' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'last_education' => 'nullable|string',
            'last_education_major' => 'nullable|string',
            'last_education_institution' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'emergency_contact_relation' => 'nullable|string',

            'create_account' => 'nullable|boolean',
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->whereNotNull('email')->ignore($employee->user_id)],
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|in:employee,admin,hrd,manager',
        ]);

        if ($employee->user_id) {
            $user = \App\Models\User::findOrFail($employee->user_id);
            if (! empty($data['email'])) {
                $user->email = $data['email'];
            }
            if (! empty($data['password'])) {
                $user->password = bcrypt($data['password']);
            }
            if (! empty($data['role'])) {
                $user->role = $data['role'];
            }
            $user->save();
        } else {
            $userData = [
                'name' => $data['full_name'],
                'password' => bcrypt($data['password'] ?? str()->random(12)),
                'role' => $data['role'] ?? 'employee',
            ];
            if (! empty($data['email'])) {
                $userData['email'] = $data['email'];
            }
            $user = \App\Models\User::create($userData);
            $data['user_id'] = $user->id;
        }

        unset($data['create_account'], $data['email'], $data['password'], $data['role']);

        foreach (['full_name', 'nickname', 'employee_code'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->cleanString($data[$field]);
            }
        }

        $employee->update($data);
        $employee->load(['company', 'department', 'position', 'jobLevel', 'jobGrade', 'status']);

        return response()->json([
            'message' => 'Data karyawan berhasil diperbarui',
            'data' => $employee,
        ]);
    }

    // DELETE /admin/employees/{id}
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        if ($employee->user && $employee->user->is_active) {
            return response()->json([
                'message' => 'Tidak dapat menghapus karyawan yang akunnya masih aktif. Nonaktifkan akun terlebih dahulu.',
            ], 422);
        }

        $employee->delete();

        return response()->json(['message' => 'Karyawan berhasil dihapus']);
    }

    // GET /admin/employees/options
    public function options()
    {
        return response()->json([
            'companies' => Company::select('id', 'name')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'positions' => \App\Models\Position::select('id', 'name')->orderBy('name')->get(),
            'job_levels' => \App\Models\JobLevel::select('id', 'name')->orderBy('name')->get(),
            'job_grades' => \App\Models\JobGrade::select('id', 'name', 'code')->orderBy('name')->get(),
            'statuses' => \App\Models\EmployeeStatus::select('id', 'label as name')
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function dropdown()
    {
        $data = \Cache::remember('employees_dropdown', 300, function () {
            $employees = Employee::select(
                'id', 'employee_code', 'full_name', 'photo_url', 'department_id'
            )
                ->orderBy('full_name')
                ->get()
                ->toArray();

            return json_encode(
                ['data' => $employees],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            );
        });

        return response($data, 200)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Content-Length', strlen($data));
    }

    public function dropdownShift()
    {
        $employees = Employee::select(
            'id', 'employee_code', 'full_name', 'photo_url',
            'department_id', 'position_id', 'job_grade_id'
        )
            ->with([
                'position:id,name',
                'jobGrade:id,name,code',
            ])
            ->orderBy('full_name')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'employee_code' => $e->employee_code,
                'full_name' => $e->full_name,
                'photo_url' => $e->photo_url,
                'department_id' => $e->department_id,
                'position' => $e->position
                    ? ['id' => $e->position->id, 'name' => $e->position->name]
                    : null,
                'job_grade' => $e->jobGrade
                    ? ['id' => $e->jobGrade->id, 'name' => $e->jobGrade->name, 'code' => $e->jobGrade->code]
                    : null,
            ]);

        $data = json_encode(
            ['data' => $employees],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return response($data, 200)
            ->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function dropdownLokasi()
    {
        $employees = Employee::select(
            'id', 'employee_code', 'full_name', 'photo_url',
            'department_id', 'position_id'
        )
            ->with([
            'department:id,name',
            'position:id,name',
        ])
            ->orderBy('full_name')
            ->get()
            ->map(fn ($e) => [
            'id' => $e->id,
            'employee_code' => $e->employee_code,
            'full_name' => $e->full_name,
            'photo_url' => $e->photo_url,
            'department' => $e->department
                ? ['id' => $e->department->id, 'name' => $e->department->name]
                : null,
            'position' => $e->position
                ? ['id' => $e->position->id, 'name' => $e->position->name]
                : null,
        ]);

        $data = json_encode(
            ['data' => $employees],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return response($data, 200)
            ->header('Content-Type', 'application/json; charset=utf-8');
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
