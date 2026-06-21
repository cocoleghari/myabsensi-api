<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\JobLevel;
use Illuminate\Http\Request;

class JobLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = JobLevel::with('company:id,name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
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

        $jobLevels = $query->orderBy('order')->paginate(15)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.job-level', compact('jobLevels', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.job-level-form', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $exists = JobLevel::where('company_id', $data['company_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Nama level sudah dipakai di perusahaan ini.');
        }

        JobLevel::create([
            'company_id' => $data['company_id'],
            'name' => $data['name'],
            'order' => $data['order'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.job-level.index')->with('success', 'Job level berhasil ditambahkan.');
    }

    public function edit(JobLevel $jobLevel)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.job-level-form', compact('jobLevel', 'companies'));
    }

    public function update(Request $request, JobLevel $jobLevel)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $exists = JobLevel::where('company_id', $data['company_id'])
            ->where('name', $data['name'])
            ->where('id', '!=', $jobLevel->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Nama level sudah dipakai di perusahaan ini.');
        }

        $jobLevel->update([
            'company_id' => $data['company_id'],
            'name' => $data['name'],
            'order' => $data['order'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.job-level.index')->with('success', 'Job level berhasil diperbarui.');
    }

    public function destroy(JobLevel $jobLevel)
    {
        $jumlahKaryawan = Employee::where('job_level_id', $jobLevel->id)->count();

        if ($jumlahKaryawan > 0) {
            return redirect()->route('admin.job-level.index')
                ->with('error', "Job level tidak dapat dihapus karena masih digunakan oleh {$jumlahKaryawan} karyawan.");
        }

        $jobLevel->delete();

        return redirect()->route('admin.job-level.index')->with('success', 'Job level berhasil dihapus.');
    }
}
