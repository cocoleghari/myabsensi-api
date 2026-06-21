<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PusatLokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PusatLokasiController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        try {
            Log::info('GET Pusat Lokasi - Start');

            $query = PusatLokasi::query();

            // Opsional: sertakan jumlah karyawan terdaftar di tiap lokasi
            if ($request->boolean('with_employee_count')) {
                $query->withCount('employees');
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lokasi', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
            }

            if ($request->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $sortField = $request->get('sort_field', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $allowedSortFields = ['id', 'nama_lokasi', 'created_at', 'updated_at'];
            $query->orderBy(
                in_array($sortField, $allowedSortFields) ? $sortField : 'created_at',
                $sortOrder === 'asc' ? 'asc' : 'desc'
            );

            if ($request->filled('per_page') && is_numeric($request->per_page)) {
                $data = $query->paginate((int) $request->per_page);
            } else {
                $data = $query->get();
            }

            Log::info('Get pusat lokasi success');

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil diambil',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Error get pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pusat lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        try {
            Log::info('POST Pusat Lokasi - Start');
            Log::info('Request data:', $request->all());

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'nama_lokasi' => 'required|string|max:255',
                'titik_kordinat' => 'required|string|max:100',
                'keterangan' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (! $this->validasiKordinat($request->titik_kordinat, $errorMsg)) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                ], 422);
            }

            $pusatLokasi = PusatLokasi::create([
                'company_id' => $request->company_id,
                'nama_lokasi' => $request->nama_lokasi,
                'titik_kordinat' => $request->titik_kordinat,
                'keterangan' => $request->keterangan,
                'is_active' => $request->is_active ?? true,
            ]);

            Log::info('Pusat lokasi created', ['id' => $pusatLokasi->id, 'nama' => $pusatLokasi->nama_lokasi]);

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil ditambahkan',
                'data' => $pusatLokasi,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data pusat lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(int $id)
    {
        try {
            $pusatLokasi = PusatLokasi::with('employees:id,full_name,nickname,employee_code')->find($id);

            if (! $pusatLokasi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail pusat lokasi',
                'data' => $pusatLokasi,
            ]);

        } catch (\Exception $e) {
            Log::error('Error show pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pusat lokasi',
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, int $id)
    {
        try {
            $pusatLokasi = PusatLokasi::find($id);

            if (! $pusatLokasi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'company_id' => 'sometimes|exists:companies,id',
                'nama_lokasi' => 'sometimes|required|string|max:255',
                'titik_kordinat' => 'sometimes|required|string|max:100',
                'keterangan' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if ($request->has('titik_kordinat')) {
                if (! $this->validasiKordinat($request->titik_kordinat, $errorMsg)) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                    ], 422);
                }
            }

            $pusatLokasi->update(
                $request->only(['company_id', 'nama_lokasi', 'titik_kordinat', 'keterangan', 'is_active'])
            );

            Log::info('Pusat lokasi updated', ['id' => $pusatLokasi->id]);

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil diupdate',
                'data' => $pusatLokasi,
            ]);

        } catch (\Exception $e) {
            Log::error('Error update pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data pusat lokasi',
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(int $id)
    {
        try {
            $pusatLokasi = PusatLokasi::find($id);

            if (! $pusatLokasi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            // Cek apakah masih dipakai oleh karyawan
            $jumlahKaryawan = $pusatLokasi->employees()->count();
            if ($jumlahKaryawan > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Pusat lokasi tidak dapat dihapus karena masih digunakan oleh {$jumlahKaryawan} karyawan. Hapus relasi terlebih dahulu.",
                ], 422);
            }

            $pusatLokasi->delete();

            Log::info('Pusat lokasi deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Error delete pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pusat lokasi',
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY MULTIPLE
    // =========================================================================

    public function destroyMultiple(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:pusat_lokasis,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Cek apakah ada yang masih dipakai
            $dipakai = PusatLokasi::whereIn('id', $request->ids)
                ->withCount('employees')
                ->having('employees_count', '>', 0)
                ->pluck('nama_lokasi')
                ->toArray();

            if (! empty($dipakai)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beberapa lokasi masih digunakan oleh karyawan: '.implode(', ', $dipakai),
                ], 422);
            }

            $count = PusatLokasi::whereIn('id', $request->ids)->delete();

            Log::info('Multiple pusat lokasi deleted', ['ids' => $request->ids, 'count' => $count]);

            return response()->json([
                'success' => true,
                'message' => "{$count} data pusat lokasi berhasil dihapus",
            ]);

        } catch (\Exception $e) {
            Log::error('Error delete multiple pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pusat lokasi',
            ], 500);
        }
    }

    public function bulkAssignEmployees(Request $request, int $id)
    {
        try {
            $pusatLokasi = PusatLokasi::find($id);

            if (! $pusatLokasi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'employee_ids' => 'required|array|min:1',
                'employee_ids.*' => 'integer|exists:employees,id',
                'radius_meter' => 'nullable|integer|min:10|max:50000',
                'keterangan' => 'nullable|string|max:255',
                'overwrite' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $radiusMeter = $request->input('radius_meter', 100);
            $keterangan = $request->input('keterangan');
            $overwrite = $request->boolean('overwrite', false);
            $employeeIds = array_unique($request->input('employee_ids'));

            if ($overwrite) {
                \App\Models\EmployeePusatLokasi::where('pusat_lokasi_id', $pusatLokasi->id)->delete();
            }

            $now = now();
            $rows = [];

            foreach ($employeeIds as $employeeId) {
                if (! $overwrite) {
                    $exists = \App\Models\EmployeePusatLokasi::where('pusat_lokasi_id', $pusatLokasi->id)
                        ->where('employee_id', $employeeId)
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                }

                $rows[] = [
                    'pusat_lokasi_id' => $pusatLokasi->id,
                    'employee_id' => $employeeId,
                    'radius_meter' => $radiusMeter,
                    'keterangan' => $keterangan,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($rows)) {
                \App\Models\EmployeePusatLokasi::insert($rows);
            }

            $totalAssigned = \App\Models\EmployeePusatLokasi::where('pusat_lokasi_id', $pusatLokasi->id)->count();
            $newlyInserted = count($rows);
            $skipped = count($employeeIds) - $newlyInserted;

            Log::info('Bulk assign employees to pusat lokasi', [
                'pusat_lokasi_id' => $pusatLokasi->id,
                'newly_inserted' => $newlyInserted,
                'skipped' => $skipped,
                'total_assigned' => $totalAssigned,
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$newlyInserted} karyawan berhasil di-assign ke {$pusatLokasi->nama_lokasi}".
                                    ($skipped > 0 ? ", {$skipped} dilewati (sudah terdaftar)" : ''),
                'total_assigned' => $totalAssigned,
                'newly_inserted' => $newlyInserted,
                'skipped' => $skipped,
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk assign employees: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan bulk assign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================

    /**
     * Validasi format koordinat "lat,lng".
     * Mengisi $errorMsg jika gagal.
     */
    private function validasiKordinat(string $kordinat, ?string &$errorMsg): bool
    {
        $parts = explode(',', $kordinat);

        if (count($parts) !== 2) {
            $errorMsg = 'Format koordinat tidak valid. Gunakan format: lat,lng (contoh: -7.797068,110.370529)';

            return false;
        }

        [$lat, $lng] = [trim($parts[0]), trim($parts[1])];

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            $errorMsg = 'Koordinat harus berupa angka';

            return false;
        }

        return true;
    }
}
