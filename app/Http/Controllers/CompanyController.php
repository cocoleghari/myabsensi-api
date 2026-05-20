<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * GET /admin/companies
     * Dengan search, filter is_active, pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::withCount(['departments', 'employees']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%')
                    ->orWhere('legal_name', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $companies = $query->orderBy('name')->get();

        return response()->json([
            'data' => $companies,
            'total' => $companies->count(),
        ]);
    }

    /**
     * GET /admin/companies-list
     * Dropdown ringan — hanya id, name, code
     */
    public function list(): JsonResponse
    {
        $companies = Company::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $companies]);
    }

    /**
     * POST /admin/companies
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'npwp' => 'nullable|string|unique:companies,npwp',
            'nib' => 'nullable|string|unique:companies,nib',
            'established_date' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'work_days' => 'nullable|in:5,6',
            'default_clock_in' => 'nullable|date_format:H:i',
            'default_clock_out' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $company = Company::create([
            'name' => $request->name,
            'legal_name' => $request->legal_name,
            'code' => strtoupper($request->code),
            'industry' => $request->industry,
            'description' => $request->description,
            'npwp' => $request->npwp,
            'nib' => $request->nib,
            'established_date' => $request->established_date,
            'email' => $request->email,
            'phone' => $request->phone,
            'fax' => $request->fax,
            'website' => $request->website,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'country' => $request->country ?? 'Indonesia',
            'timezone' => $request->timezone ?? 'Asia/Jakarta',
            'work_days' => $request->work_days ?? '5',
            'default_clock_in' => $request->default_clock_in ?? '08:00',
            'default_clock_out' => $request->default_clock_out ?? '17:00',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Company berhasil dibuat',
            'data' => $company,
        ], 201);
    }

    /**
     * GET /admin/companies/{id}
     */
    public function show(int $id): JsonResponse
    {
        $company = Company::withCount(['departments', 'employees'])
            ->findOrFail($id);

        return response()->json(['data' => $company]);
    }

    /**
     * PUT /admin/companies/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'code' => 'sometimes|string|max:50|unique:companies,code,'.$id,
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'npwp' => 'nullable|string|unique:companies,npwp,'.$id,
            'nib' => 'nullable|string|unique:companies,nib,'.$id,
            'established_date' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'work_days' => 'nullable|in:5,6',
            'default_clock_in' => 'nullable|date_format:H:i',
            'default_clock_out' => 'nullable|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper($request->code)]);
        }

        $company->update($request->only([
            'name', 'legal_name', 'code', 'industry', 'description',
            'npwp', 'nib', 'established_date', 'email', 'phone', 'fax',
            'website', 'address', 'city', 'province', 'postal_code',
            'country', 'timezone', 'work_days', 'default_clock_in',
            'default_clock_out', 'is_active',
        ]));

        return response()->json([
            'message' => 'Company berhasil diperbarui',
            'data' => $company->fresh(),
        ]);
    }

    /**
     * DELETE /admin/companies/{id}
     * Soft delete. Tolak jika masih punya karyawan atau departemen aktif.
     */
    public function destroy(int $id): JsonResponse
    {
        $company = Company::withCount(['departments', 'employees'])
            ->findOrFail($id);

        if ($company->employees_count > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus company yang masih memiliki karyawan.',
            ], 422);
        }

        if ($company->departments_count > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus company yang masih memiliki department.',
            ], 422);
        }

        $company->delete();

        return response()->json(['message' => 'Company berhasil dihapus']);
    }
}
