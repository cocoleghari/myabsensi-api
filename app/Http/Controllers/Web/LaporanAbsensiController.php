<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Department;
use App\Models\PusatLokasi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanAbsensiController extends Controller
{
    public function index(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::now()->format('Y-m-d'));

        $query = Absensi::query()
            ->with([
                'employee:id,employee_code,full_name,nik,department_id,position_id',
                'employee.department:id,name',
                'employee.position:id,name',
                'pusatLokasi:id,nama_lokasi',
            ])
            ->whereBetween('tanggal_absen', [$tanggalMulai, $tanggalSelesai])
            ->orderByDesc('tanggal_absen')
            ->orderByDesc('waktu_absen');

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', '%'.$request->search.'%')
                    ->orWhere('nik', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('pusat_lokasi_id')) {
            $query->where('pusat_lokasi_id', $request->pusat_lokasi_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tipe_absen')) {
            $query->where('tipe_absen', $request->tipe_absen);
        }

        $absensi = $query->paginate(20)->withQueryString();

        $departments = Department::select('id', 'name')->orderBy('name')->get();
        $pusatLokasis = PusatLokasi::select('id', 'nama_lokasi')->orderBy('nama_lokasi')->get();

        return view('admin.laporan-absensi', compact(
            'absensi', 'departments', 'pusatLokasis', 'tanggalMulai', 'tanggalSelesai'
        ));
    }
}
