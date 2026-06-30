<?php

namespace App\Http\Controllers\Web;

use App\Helpers\ScopeHelper;
use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Department;
use App\Models\TipeAktivitas;
use Illuminate\Http\Request;

class LaporanAktivitasController extends Controller
{
    public function index(Request $request)
    {
        $query = Aktivitas::with([
            'employee:id,full_name,nik,employee_code,department_id,position_id',
            'employee.department:id,name',
            'employee.position:id,name',
            'tipeAktivitas:id,nama',
        ]);

        // ── Filter departemen untuk manager/supervisor ──────────────
        ScopeHelper::applyDepartmentScope($query);

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', '%'.$request->search.'%')
                    ->orWhere('employee_code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id)
            );
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('mulai', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('mulai', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('tipe_aktivitas_id')) {
            $query->where('tipe_aktivitas_id', $request->tipe_aktivitas_id);
        }

        $aktivitas = $query->orderBy('mulai', 'desc')
            ->paginate(20)
            ->withQueryString();

        $departmentQuery = Department::select('id', 'name')->orderBy('name');
        if (ScopeHelper::isLimitedRole()) {
            $deptId = ScopeHelper::getDepartmentIds();
            $departmentQuery->where('id', $deptId);
        }
        $departments = $departmentQuery->get();

        $tipeAktivitasList = TipeAktivitas::select('id', 'nama')->orderBy('nama')->get();
        $tanggalMulai = $request->tanggal_mulai ?? now()->startOfMonth()->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->toDateString();

        return view('admin.laporan-aktivitas', compact('aktivitas', 'departments', 'tipeAktivitasList', 'tanggalMulai',
            'tanggalSelesai'));
    }

    public function export(Request $request)
    {
        if (ScopeHelper::isLimitedRole()) {
            $deptId = ScopeHelper::getDepartmentId();
            if ($deptId) {
                $request->merge(['department_id' => $deptId]);
            }
        }

        if (! $request->filled('tanggal_mulai')) {
            $request->merge(['tanggal_mulai' => now()->startOfMonth()->toDateString()]);
        }
        if (! $request->filled('tanggal_selesai')) {
            $request->merge(['tanggal_selesai' => now()->toDateString()]);
        }

        return app(\App\Http\Controllers\Mobile\LaporanAktivitasController::class)->export($request);
    }
}
