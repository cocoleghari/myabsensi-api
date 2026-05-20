<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePusatLokasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Mengelola relasi many-to-many antara karyawan dan pusat lokasi absensi.
 * Menggantikan LokasiController yang lama (tabel lokasis).
 *
 * Endpoint utama:
 *   GET    /admin/employee-lokasi                  → daftar semua relasi (opsional filter employee)
 *   POST   /admin/employee-lokasi                  → tambah relasi karyawan ↔ pusat lokasi
 *   PUT    /admin/employee-lokasi/{id}             → update radius / keterangan
 *   DELETE /admin/employee-lokasi/{id}             → hapus satu relasi
 *   GET    /admin/employee-lokasi/employee/{id}    → semua lokasi milik satu karyawan
 *   GET    /admin/employees                        → daftar karyawan (untuk dropdown)
 */
class EmployeePusatLokasiController extends Controller
{
    // =========================================================================
    // INDEX — Daftar semua relasi (opsional filter per karyawan)
    // =========================================================================

    public function index(Request $request): JsonResponse
    {
        try {
            $query = EmployeePusatLokasi::with([
                'employee:id,full_name,nickname,employee_code,nik',
                'pusatLokasi:id,nama_lokasi,titik_kordinat,is_active',
            ]);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->filled('pusat_lokasi_id')) {
                $query->where('pusat_lokasi_id', $request->pusat_lokasi_id);
            }

            $data = $query->orderBy('employee_id')->get();

            Log::info('EmployeePusatLokasi index - total: '.$data->count());

            return response()->json([
                'success' => true,
                'message' => 'Data lokasi karyawan berhasil diambil',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Error index EmployeePusatLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi karyawan',
            ], 500);
        }
    }

    // =========================================================================
    // STORE — Tambah relasi karyawan ↔ pusat lokasi
    // =========================================================================

    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Store EmployeePusatLokasi - data: ', $request->all());

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'pusat_lokasi_id' => 'required|exists:pusat_lokasis,id',
                'radius_meter' => 'nullable|integer|min:10|max:10000',
                'keterangan' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Cek duplikat
            $existing = EmployeePusatLokasi::where('employee_id', $request->employee_id)
                ->where('pusat_lokasi_id', $request->pusat_lokasi_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan ini sudah terdaftar di pusat lokasi tersebut',
                    'existing_data' => $existing->load([
                        'employee:id,full_name,nickname',
                        'pusatLokasi:id,nama_lokasi',
                    ]),
                ], 422);
            }

            $pivot = EmployeePusatLokasi::create([
                'employee_id' => $request->employee_id,
                'pusat_lokasi_id' => $request->pusat_lokasi_id,
                'radius_meter' => $request->radius_meter ?? 100,
                'keterangan' => $request->keterangan,
            ]);

            Log::info('EmployeePusatLokasi created: id='.$pivot->id);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi karyawan berhasil ditambahkan',
                'data' => $pivot->load([
                    'employee:id,full_name,nickname,employee_code',
                    'pusatLokasi:id,nama_lokasi,titik_kordinat',
                ]),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store EmployeePusatLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan lokasi karyawan: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE — Ubah radius / keterangan pada relasi
    // =========================================================================

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $pivot = EmployeePusatLokasi::find($id);

            if (! $pivot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi karyawan tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'radius_meter' => 'nullable|integer|min:10|max:10000',
                'keterangan' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pivot->update($request->only(['radius_meter', 'keterangan']));

            Log::info('EmployeePusatLokasi updated: id='.$id);

            return response()->json([
                'success' => true,
                'message' => 'Data lokasi karyawan berhasil diperbarui',
                'data' => $pivot->load([
                    'employee:id,full_name,nickname,employee_code',
                    'pusatLokasi:id,nama_lokasi,titik_kordinat',
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Error update EmployeePusatLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui lokasi karyawan',
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — Hapus satu relasi
    // =========================================================================

    public function destroy(int $id): JsonResponse
    {
        try {
            $pivot = EmployeePusatLokasi::find($id);

            if (! $pivot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi karyawan tidak ditemukan',
                ], 404);
            }

            $pivot->delete();

            Log::info('EmployeePusatLokasi deleted: id='.$id);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi karyawan berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Error destroy EmployeePusatLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus lokasi karyawan',
            ], 500);
        }
    }

    // =========================================================================
    // BY EMPLOYEE — Semua lokasi milik satu karyawan
    // =========================================================================

    public function byEmployee(int $employeeId): JsonResponse
    {
        try {
            $employee = Employee::find($employeeId);

            if (! $employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan',
                ], 404);
            }

            $lokasis = EmployeePusatLokasi::where('employee_id', $employeeId)
                ->with('pusatLokasi:id,nama_lokasi,titik_kordinat,is_active')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data lokasi karyawan berhasil diambil',
                'employee' => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'nickname' => $employee->nickname,
                    'employee_code' => $employee->employee_code,
                ],
                'data' => $lokasis,
            ]);

        } catch (\Exception $e) {
            Log::error('Error byEmployee EmployeePusatLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi karyawan',
            ], 500);
        }
    }

    // =========================================================================
    // EMPLOYEES LIST — Untuk dropdown di form admin
    // =========================================================================

    public function employees(): JsonResponse
    {
        try {
            $employees = Employee::select('id', 'full_name', 'nickname', 'employee_code', 'nik')
                ->orderBy('full_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);

        } catch (\Exception $e) {
            Log::error('Error employees list: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data karyawan',
            ], 500);
        }
    }

    // =========================================================================
    // CEK DUPLIKAT — Cek sebelum submit form
    // =========================================================================

    public function cekDuplikat(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'pusat_lokasi_id' => 'required|exists:pusat_lokasis,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $exists = EmployeePusatLokasi::where('employee_id', $request->employee_id)
                ->where('pusat_lokasi_id', $request->pusat_lokasi_id)
                ->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Relasi sudah ada' : 'Relasi tersedia',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal cek duplikat',
            ], 500);
        }
    }
}
