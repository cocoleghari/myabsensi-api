<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAktivitasRequest;
use App\Http\Requests\UpdateAktivitasRequest;
use App\Models\Aktivitas;
use App\Models\AktivitasFoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AktivitasController extends Controller
{
    // =========================================================================
    // INDEX — GET /user/aktivitas?tanggal=2026-04-10
    // =========================================================================

    public function index(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $query = Aktivitas::with(['fotos', 'tipeAktivitas'])
            ->where('employee_id', $employee->id)
            ->orderBy('mulai', 'desc');

        if ($request->filled('tanggal')) {
            $query->whereDate('mulai', $request->tanggal);
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereMonth('mulai', $request->bulan)
                ->whereYear('mulai', $request->tahun);
        }

        $aktivitas = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Data aktivitas berhasil diambil',
            'data' => $aktivitas,
        ]);
    }

    // =========================================================================
    // STORE — POST /user/aktivitas
    // =========================================================================

    public function store(StoreAktivitasRequest $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        DB::beginTransaction();
        try {
            $aktivitas = Aktivitas::create([
                'employee_id' => $employee->id,
                'tugas' => $request->tugas,
                'mulai' => $request->mulai,
                'berakhir' => $request->berakhir,
                'tipe_aktivitas_id' => $request->tipe_aktivitas_id,
                'tujuan' => $request->tujuan,
                'kendaraan_nopol' => $request->kendaraan_nopol,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'akurasi_meter' => $request->akurasi_meter,
            ]);

            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $index => $foto) {
                    $path = $foto->store('foto_aktivitas', 'public');
                    AktivitasFoto::create([
                        'aktivitas_id' => $aktivitas->id,
                        'foto_path' => $path,
                        'urutan' => $index,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Aktivitas berhasil disimpan',
                'data' => $aktivitas->load(['fotos', 'tipeAktivitas']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan aktivitas: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SHOW — GET /user/aktivitas/{id}
    // =========================================================================

    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);
        $aktivitas = Aktivitas::with(['fotos', 'tipeAktivitas'])
            ->where('employee_id', $employee->id)
            ->find($id);

        if (! $aktivitas) {
            return response()->json([
                'status' => false,
                'message' => 'Aktivitas tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail aktivitas berhasil diambil',
            'data' => $aktivitas,
        ]);
    }

    // =========================================================================
    // UPDATE — PUT /user/aktivitas/{id}
    // =========================================================================

    public function update(UpdateAktivitasRequest $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);
        $aktivitas = Aktivitas::where('employee_id', $employee->id)->find($id);

        if (! $aktivitas) {
            return response()->json([
                'status' => false,
                'message' => 'Aktivitas tidak ditemukan',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $aktivitas->update($request->only([
                'tugas', 'mulai', 'berakhir',
                'tipe_aktivitas_id', 'tujuan', 'kendaraan_nopol',
                'latitude', 'longitude', 'akurasi_meter',
            ]));

            // Hapus foto yang dipilih
            if ($request->filled('hapus_foto_ids')) {
                $fotos = AktivitasFoto::whereIn('id', $request->hapus_foto_ids)
                    ->where('aktivitas_id', $aktivitas->id)
                    ->get();

                foreach ($fotos as $foto) {
                    Storage::disk('public')->delete($foto->foto_path);
                    $foto->delete();
                }
            }

            // Upload foto baru
            if ($request->hasFile('fotos')) {
                $lastUrutan = $aktivitas->fotos()->max('urutan') ?? -1;
                foreach ($request->file('fotos') as $index => $foto) {
                    $path = $foto->store('foto_aktivitas', 'public');
                    AktivitasFoto::create([
                        'aktivitas_id' => $aktivitas->id,
                        'foto_path' => $path,
                        'urutan' => $lastUrutan + $index + 1,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Aktivitas berhasil diperbarui',
                'data' => $aktivitas->load(['fotos', 'tipeAktivitas']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui aktivitas: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — DELETE /user/aktivitas/{id}
    // =========================================================================

    public function destroy(Request $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);
        $aktivitas = Aktivitas::where('employee_id', $employee->id)->find($id);

        if (! $aktivitas) {
            return response()->json([
                'status' => false,
                'message' => 'Aktivitas tidak ditemukan',
            ], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($aktivitas->fotos as $foto) {
                Storage::disk('public')->delete($foto->foto_path);
            }

            $aktivitas->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Aktivitas berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus aktivitas: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================

    /**
     * Ambil employee dari user yang sedang login.
     */
    private function getEmployee(Request $request): \App\Models\Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            abort(403, 'Profil karyawan tidak ditemukan. Hubungi admin.');
        }

        return $employee;
    }
}
