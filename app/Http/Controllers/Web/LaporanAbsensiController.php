<?php

namespace App\Http\Controllers\Web;

use App\Helpers\ScopeHelper;
use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Department;
use App\Models\PusatLokasi;
use Illuminate\Http\Request;

class LaporanAbsensiController extends Controller
{
    public function index(Request $request)
    {
        $query = Absensi::with([
            'employee:id,full_name,nik,employee_code,department_id,position_id',
            'employee.department:id,name',
            'employee.position:id,name',
            'pusatLokasi:id,nama_lokasi',
            'shift:id,nama,tipe',
        ]);

        // ── Filter departemen untuk manager/supervisor ──────────────
        ScopeHelper::applyDepartmentScope($query);

        // ── Filter dari request ─────────────────────────────────────
        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', '%'.$request->search.'%')
                    ->orWhere('nik', 'like', '%'.$request->search.'%')
                    ->orWhere('employee_code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id)
            );
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_absen', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_absen', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tipe_absen')) {
            $query->where('tipe_absen', $request->tipe_absen);
        }

        $absensi = $query->orderBy('tanggal_absen', 'desc')
            ->orderBy('waktu_absen', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Hanya tampilkan department yang relevan di filter
        $departmentQuery = Department::select('id', 'name')->orderBy('name');
        if (ScopeHelper::isLimitedRole()) {
            $deptId = ScopeHelper::getDepartmentIds();
            $departmentQuery->where('id', $deptId);
        }
        $departments = $departmentQuery->get();

        $pusatLokasis = PusatLokasi::select('id', 'nama_lokasi')->orderBy('nama_lokasi')->get();

        // Statistik ringkasan
        $statsQuery = clone $query;
        $stats = [
            'total' => $absensi->total(),
            'tepat_waktu' => (clone $statsQuery)->where('status', 'tepat_waktu')->count(),
            'terlambat' => (clone $statsQuery)->where('status', 'terlambat')->count(),
            'hadir' => (clone $statsQuery)->where('status', 'hadir')->count(),
        ];

        $tanggalMulai = $request->tanggal_mulai ?? now()->startOfMonth()->toDateString();
        $tanggalSelesai = $request->tanggal_selesai ?? now()->toDateString();

        return view('admin.laporan-absensi', compact('absensi', 'departments', 'pusatLokasis', 'stats', 'tanggalMulai',
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

        dd($request->all()); // ← tambah sementara, cek apakah department_id ada

        return app(\App\Http\Controllers\Mobile\LaporanAbsensiController::class)->export($request);
    }
}
