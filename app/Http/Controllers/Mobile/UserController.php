<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /admin/users
     * Daftar semua user beserta profil karyawan.
     */
    public function index(Request $request)
    {
        $query = User::select('id', 'name', 'email', 'role', 'is_active', 'created_at')
            ->with([
                'employee:id,user_id,full_name,employee_code,photo_url,department_id,position_id,employment_type,join_date',
                'employee.department:id,name',
                'employee.position:id,name',
            ]);

        if ($request->filled('role')) {
            $query->whereIn('role', (array) $request->role);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('full_name', 'like', $search)
                            ->orWhere('employee_code', 'like', $search)
                            ->orWhere('nik', 'like', $search);
                    });
            });
        }

        $perPage = $request->get('per_page', 20);
        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data user berhasil diambil',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * GET /admin/users/{id}
     */
    public function show($id)
    {
        // ← GANTI: cari via employee, bukan user langsung
        $user = User::with([
            'employee.company:id,name',
            'employee.department:id,name',
            'employee.position:id,name',
            'employee.status:id,label',
        ])->whereHas('employee', function ($q) use ($id) {
            $q->where('id', $id);  // ← cari by employee.id
        })->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak ditemukan'], 404);
        }

        return response()->json(['status' => true, 'data' => $user]);
    }

    /**
     * GET /admin/employees
     * Daftar karyawan (dari tabel employees) dengan filter department/position.
     */
    public function employees(Request $request)
    {
        $query = Employee::with([
            'user:id,email,role,is_active',
            'department:id,name',
            'position:id,name',
            'company:id,name',
            'employeeStatus:id,label,color',
        ]);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        if ($request->filled('employment_type')) {
            $query->whereIn('employment_type', (array) $request->employment_type);
        }

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                    ->orWhere('employee_code', 'like', $search)
                    ->orWhere('nik', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        $perPage = $request->get('per_page', 20);
        $employees = $query->orderBy('full_name')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data karyawan berhasil diambil',
            'data' => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    /**
     * GET /admin/employees/{id}
     */
    public function showEmployee($id)
    {
        $employee = Employee::with([
            'user:id,email,role,is_active',
            'company',
            'department',
            'position.jobLevel',
            'position.jobGrade',
            'employeeStatus',
            'employeeShifts.shift',
        ])->find($id);

        if (! $employee) {
            return response()->json(['status' => false, 'message' => 'Karyawan tidak ditemukan'], 404);
        }

        return response()->json(['status' => true, 'data' => $employee]);
    }
}
