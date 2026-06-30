<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = Shift::with('company:id,name')->withCount('employeeShifts');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%'.$request->search.'%')
                    ->orWhere('kode', 'like', '%'.$request->search.'%');
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

        $shifts = $query->orderBy('nama')->paginate(15)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.master-shift', compact('shifts', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.master-shift-form', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->merge(['kode' => strtoupper(trim($request->kode ?? ''))]);

        $isFlex = $request->input('tipe') === 'flex';

        $data = $request->validate($this->rules(null, $isFlex));

        Shift::create($this->buildData($request, $data, $isFlex));

        return redirect()->route('admin.master-shift.index')
            ->with('success', 'Shift berhasil ditambahkan.');
    }

    public function edit(Shift $shift)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.master-shift-form', compact('shift', 'companies'));
    }

    public function update(Request $request, Shift $shift)
    {
        $request->merge(['kode' => strtoupper(trim($request->kode ?? ''))]);

        $isFlex = $request->input('tipe') === 'flex';

        $data = $request->validate($this->rules($shift->id, $isFlex));

        $shift->update($this->buildData($request, $data, $isFlex));

        return redirect()->route('admin.master-shift.index')
            ->with('success', 'Shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift)
    {
        $aktif = $shift->employeeShifts()
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
            ->exists();

        if ($aktif) {
            return redirect()->route('admin.master-shift.index')
                ->with('error', 'Shift tidak dapat dihapus karena masih ada karyawan yang menggunakan shift ini.');
        }

        $shift->delete();

        return redirect()->route('admin.master-shift.index')
            ->with('success', 'Shift berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function rules(?int $ignoreId = null, bool $isFlex = false): array
    {
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'tipe' => 'required|in:reguler,flex',
            'nama' => 'required|string|max:100',
            'kode' => [
                'required', 'string', 'max:20',
                Rule::unique('shifts', 'kode')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'keterangan' => 'nullable|string|max:500',
        ];

        // Field jam hanya wajib untuk shift reguler
        if (! $isFlex) {
            $rules['jam_masuk'] = 'required|date_format:H:i,H:i:s';
            $rules['jam_pulang'] = 'required|date_format:H:i,H:i:s';
            $rules['batas_waktu_pulang'] = 'required|date_format:H:i,H:i:s';
            $rules['toleransi_terlambat_menit'] = 'required|integer|min:0|max:240';
            $rules['window_masuk_awal_menit'] = 'required|integer|min:0|max:240';
        }

        return $rules;
    }

    private function buildData(Request $request, array $data, bool $isFlex): array
    {
        // Untuk flex: isi nilai default agar kolom NOT NULL tidak error
        $jamMasuk = $isFlex ? '00:00' : $this->normalizeTime($data['jam_masuk']);
        $jamPulang = $isFlex ? '23:59' : $this->normalizeTime($data['jam_pulang']);
        $batasWaktuPulang = $isFlex ? '23:59' : $this->normalizeTime($data['batas_waktu_pulang']);
        $toleransi = $isFlex ? 0 : (int) $data['toleransi_terlambat_menit'];
        $windowMasuk = $isFlex ? 0 : (int) $data['window_masuk_awal_menit'];
        $melewatiMalam = $isFlex ? false : $request->boolean('melewati_tengah_malam');

        return [
            'company_id' => $data['company_id'],
            'tipe' => $data['tipe'],
            'nama' => $data['nama'],
            'kode' => $data['kode'],
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'toleransi_terlambat_menit' => $toleransi,
            'window_masuk_awal_menit' => $windowMasuk,
            'melewati_tengah_malam' => $melewatiMalam,
            'batas_waktu_pulang' => $batasWaktuPulang,
            'berlaku_hari_libur' => $request->boolean('berlaku_hari_libur'),
            'berlaku_akhir_pekan' => $request->boolean('berlaku_akhir_pekan'),
            'keterangan' => $data['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function normalizeTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
