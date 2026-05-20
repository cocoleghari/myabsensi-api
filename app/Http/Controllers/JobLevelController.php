<?php

namespace App\Http\Controllers;

use App\Models\JobLevel;
use Illuminate\Http\Request;

class JobLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = JobLevel::with('company')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id)
            )
            ->when($request->is_active !== null && $request->is_active !== '', fn ($q) => $q->where('is_active', $request->boolean('is_active'))
            )
            ->orderBy('order');

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'order' => 'integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Cek unik company_id + name
        $exists = JobLevel::where('company_id', $data['company_id'])
            ->where('name', $data['name'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Nama level sudah dipakai di perusahaan ini.'], 422);
        }

        return response()->json(JobLevel::create($data), 201);
    }

    public function show(JobLevel $jobLevel)
    {
        return response()->json($jobLevel->load('company'));
    }

    public function update(Request $request, JobLevel $jobLevel)
    {
        $data = $request->validate([
            'company_id' => 'exists:companies,id',
            'name' => 'string|max:255',
            'order' => 'integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $companyId = $data['company_id'] ?? $jobLevel->company_id;
        $name = $data['name'] ?? $jobLevel->name;

        $exists = JobLevel::where('company_id', $companyId)
            ->where('name', $name)
            ->where('id', '!=', $jobLevel->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Nama level sudah dipakai di perusahaan ini.'], 422);
        }

        $jobLevel->update($data);

        return response()->json($jobLevel->load('company'));
    }

    public function destroy(JobLevel $jobLevel)
    {
        $jobLevel->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    /** Untuk dropdown — hanya yang aktif */
    public function list(Request $request)
    {
        return response()->json(
            JobLevel::when($request->filled('company_id'),
                fn ($q) => $q->where('company_id', $request->company_id)
            )
                ->active()
                ->get(['id', 'name', 'order'])
        );
    }
}
