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

        $data = $request->validate($this->rules());

        Shift::create([
            'company_id' => $data['company_id'],
            'nama' => $data['nama'],
            'kode' => $data['kode'],
            'jam_masuk' => $this->normalizeTime($data['jam_masuk']),
            'jam_pulang' => $this->normalizeTime($data['jam_pulang']),
            'toleransi_terlambat_menit' => $data['toleransi_terlambat_menit'],
            'window_masuk_awal_menit' => $data['window_masuk_awal_menit'],
            'melewati_tengah_malam' => $request->boolean('melewati_tengah_malam'),
            'batas_waktu_pulang' => $this->normalizeTime($data['batas_waktu_pulang']),
            'berlaku_hari_libur' => $request->boolean('berlaku_hari_libur'),
            'berlaku_akhir_pekan' => $request->boolean('berlaku_akhir_pekan'),
            'keterangan' => $data['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.master-shift.index')->with('success', 'Shift berhasil ditambahkan.');
    }

    public function edit(Shift $shift)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.master-shift-form', compact('shift', 'companies'));
    }

    public function update(Request $request, Shift $shift)
    {
        $request->merge(['kode' => strtoupper(trim($request->kode ?? ''))]);

        $data = $request->validate($this->rules($shift->id));

        $shift->update([
            'company_id' => $data['company_id'],
            'nama' => $data['nama'],
            'kode' => $data['kode'],
            'jam_masuk' => $this->normalizeTime($data['jam_masuk']),
            'jam_pulang' => $this->normalizeTime($data['jam_pulang']),
            'toleransi_terlambat_menit' => $data['toleransi_terlambat_menit'],
            'window_masuk_awal_menit' => $data['window_masuk_awal_menit'],
            'melewati_tengah_malam' => $request->boolean('melewati_tengah_malam'),
            'batas_waktu_pulang' => $this->normalizeTime($data['batas_waktu_pulang']),
            'berlaku_hari_libur' => $request->boolean('berlaku_hari_libur'),
            'berlaku_akhir_pekan' => $request->boolean('berlaku_akhir_pekan'),
            'keterangan' => $data['keterangan'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.master-shift.index')->with('success', 'Shift berhasil diperbarui.');
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

        return redirect()->route('admin.master-shift.index')->with('success', 'Shift berhasil dihapus.');
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'nama' => 'required|string|max:100',
            'kode' => [
                'required', 'string', 'max:20',
                Rule::unique('shifts', 'kode')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'jam_masuk' => 'required|date_format:H:i,H:i:s',
            'jam_pulang' => 'required|date_format:H:i,H:i:s',
            'toleransi_terlambat_menit' => 'required|integer|min:0|max:240',
            'window_masuk_awal_menit' => 'required|integer|min:0|max:240',
            'batas_waktu_pulang' => 'required|date_format:H:i,H:i:s',
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    private function normalizeTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
