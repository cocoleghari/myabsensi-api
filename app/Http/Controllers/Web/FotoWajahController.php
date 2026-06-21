<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FotoWajahController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department:id,name'])
            ->select(['id', 'employee_code', 'full_name', 'nickname', 'nik', 'department_id', 'foto_wajah_path', 'wajah_terdaftar']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', '%'.$request->search.'%')
                    ->orWhere('employee_code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'terdaftar') {
                $query->where('wajah_terdaftar', true);
            } elseif ($request->status === 'belum') {
                $query->where('wajah_terdaftar', false);
            }
        }

        $karyawan = $query->orderBy('full_name')->paginate(20)->withQueryString();
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return view('admin.foto-wajah', compact('karyawan', 'departments'));
    }

    public function upload(Request $request, Employee $karyawan)
    {
        $request->validate([
            'foto_wajah' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($karyawan->foto_wajah_path && Storage::disk('public')->exists($karyawan->foto_wajah_path)) {
            Storage::disk('public')->delete($karyawan->foto_wajah_path);
        }

        $baseName = Str::slug($karyawan->nickname ?? $karyawan->full_name, '_');
        $extension = $request->file('foto_wajah')->getClientOriginalExtension();
        $filename = "wajah_{$baseName}.{$extension}";

        $path = $request->file('foto_wajah')->storeAs('wajah_referensi', $filename, 'public');

        $karyawan->update([
            'foto_wajah_path' => $path,
            'wajah_terdaftar' => true,
        ]);

        return redirect()->route('admin.foto-wajah.index')
            ->with('success', "Foto wajah {$karyawan->full_name} berhasil diunggah.");
    }

    public function reset(Employee $karyawan)
    {
        if ($karyawan->foto_wajah_path && Storage::disk('public')->exists($karyawan->foto_wajah_path)) {
            Storage::disk('public')->delete($karyawan->foto_wajah_path);
        }

        $karyawan->update([
            'foto_wajah_path' => null,
            'wajah_terdaftar' => false,
        ]);

        return redirect()->route('admin.foto-wajah.index')
            ->with('success', "Foto wajah {$karyawan->full_name} berhasil di-reset.");
    }
}
