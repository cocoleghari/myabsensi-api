<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeStatusController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /admin/employee-statuses
    // -------------------------------------------------------------------------
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeStatus::query()->orderBy('sort_order')->orderBy('label');

        if ($request->boolean('visible_only')) {
            $query->visible();
        }

        return response()->json($query->withCount('employees')->get());
    }

    // -------------------------------------------------------------------------
    // POST /admin/employee-statuses
    // -------------------------------------------------------------------------
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:employee_statuses,code'],
            'label' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'is_visible' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $status = EmployeeStatus::create($data);

        return response()->json([
            'message' => 'Status karyawan berhasil ditambahkan.',
            'data' => $status,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /admin/employee-statuses/{id}
    // -------------------------------------------------------------------------
    public function show(EmployeeStatus $employeeStatus): JsonResponse
    {
        return response()->json($employeeStatus->loadCount('employees'));
    }

    // -------------------------------------------------------------------------
    // PUT /admin/employee-statuses/{id}
    // -------------------------------------------------------------------------
    public function update(Request $request, EmployeeStatus $employeeStatus): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('employee_statuses', 'code')->ignore($employeeStatus->id)],
            'label' => ['sometimes', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'is_visible' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $employeeStatus->update($data);

        return response()->json([
            'message' => 'Status karyawan berhasil diperbarui.',
            'data' => $employeeStatus->fresh(),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /admin/employee-statuses/{id}
    // -------------------------------------------------------------------------
    public function destroy(EmployeeStatus $employeeStatus): JsonResponse
    {
        $count = $employeeStatus->employees()->count();
        if ($count > 0) {
            return response()->json([
                'message' => "Tidak bisa dihapus — masih dipakai oleh {$count} karyawan.",
            ], 422);
        }

        $employeeStatus->delete();

        return response()->json(['message' => 'Status karyawan berhasil dihapus.']);
    }

    // -------------------------------------------------------------------------
    // GET /admin/employee-statuses-list  (dropdown ringan)
    // -------------------------------------------------------------------------
    public function list(): JsonResponse
    {
        $statuses = EmployeeStatus::visible()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'label', 'color']);

        return response()->json($statuses);
    }
}
