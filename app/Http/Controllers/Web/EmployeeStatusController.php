<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EmployeeStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeStatus::withCount('employees');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('label', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($request->status === 'nonaktif') {
                $query->where('is_active', false);
            }
        }

        $statuses = $query->orderBy('sort_order')->orderBy('label')->paginate(15)->withQueryString();

        return view('admin.status-karyawan', compact('statuses'));
    }

    public function create()
    {
        return view('admin.status-karyawan-form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:employee_statuses,code',
            'label' => 'required|string|max:100',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        EmployeeStatus::create([
            'code' => $data['code'],
            'label' => $data['label'],
            'color' => $data['color'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return redirect()->route('admin.status-karyawan.index')->with('success', 'Status karyawan berhasil ditambahkan.');
    }

    public function edit(EmployeeStatus $employeeStatus)
    {
        return view('admin.status-karyawan-form', ['status' => $employeeStatus]);
    }

    public function update(Request $request, EmployeeStatus $employeeStatus)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('employee_statuses', 'code')->ignore($employeeStatus->id)],
            'label' => 'required|string|max:100',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $employeeStatus->update([
            'code' => $data['code'],
            'label' => $data['label'],
            'color' => $data['color'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return redirect()->route('admin.status-karyawan.index')->with('success', 'Status karyawan berhasil diperbarui.');
    }

    public function destroy(EmployeeStatus $employeeStatus)
    {
        $count = $employeeStatus->employees()->count();

        if ($count > 0) {
            return redirect()->route('admin.status-karyawan.index')
                ->with('error', "Status tidak dapat dihapus karena masih dipakai oleh {$count} karyawan.");
        }

        $employeeStatus->delete();

        return redirect()->route('admin.status-karyawan.index')->with('success', 'Status karyawan berhasil dihapus.');
    }
}
