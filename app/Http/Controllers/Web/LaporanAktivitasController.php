<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Department;
use App\Models\TipeAktivitas;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanAktivitasController extends Controller
{
    public function index(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::now()->format('Y-m-d'));

        $query = Aktivitas::query()
            ->with([
                'employee:id,employee_code,full_name,nik,department_id',
                'employee.department:id,name',
                'tipeAktivitas:id,nama',
                'fotos' => fn ($q) => $q->orderBy('urutan'),
            ])
            ->whereBetween('mulai', [
                $tanggalMulai.' 00:00:00',
                $tanggalSelesai.' 23:59:59',
            ])
            ->orderByDesc('mulai');

        if ($request->filled('search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('full_name', 'like', '%'.$request->search.'%')
                    ->orWhere('nik', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('tipe_aktivitas_id')) {
            $query->where('tipe_aktivitas_id', $request->tipe_aktivitas_id);
        }

        $aktivitas = $query->paginate(20)->withQueryString();

        $departments = Department::select('id', 'name')->orderBy('name')->get();
        $tipeAktivitasList = TipeAktivitas::select('id', 'nama')->orderBy('nama')->get();

        return view('admin.laporan-aktivitas', compact(
            'aktivitas', 'departments', 'tipeAktivitasList', 'tanggalMulai', 'tanggalSelesai'
        ));
    }
}
