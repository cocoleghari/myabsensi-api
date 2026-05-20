<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeShiftController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/employee-shifts
    // Daftar semua assignment shift
    // Query params: employee_id, shift_id, aktif (1/0), per_page
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = EmployeeShift::with([
            'employee:id,full_name,employee_code,photo_url',
            'shift:id,nama,kode,jam_masuk,jam_pulang,melewati_tengah_malam',
        ]);

        // Filter per karyawan
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter per shift
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // Filter hanya yang aktif saat ini
        if ($request->filled('aktif')) {
            $aktif = filter_var($request->aktif, FILTER_VALIDATE_BOOLEAN);
            if ($aktif) {
                $query->aktifPada(now()); // scope dari EmployeeShift model
            } else {
                // Sudah berakhir: tanggal_selesai < hari ini
                $query->whereNotNull('tanggal_selesai')
                    ->where('tanggal_selesai', '<', now()->toDateString());
            }
        }

        $perPage = (int) $request->get('per_page', 30);

        $results = $query
            ->orderByDesc('tanggal_mulai')
            ->paginate($perPage);

        // Flatten relasi agar Flutter mudah parsing
        $items = collect($results->items())->map(fn ($es) => $this->formatItem($es));

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/employee-shifts/{employeeShift}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(EmployeeShift $employeeShift): JsonResponse
    {
        $employeeShift->load([
            'employee:id,full_name,employee_code,photo_url',
            'shift:id,nama,kode,jam_masuk,jam_pulang,melewati_tengah_malam',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatItem($employeeShift),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/employee-shifts
    // Assign shift baru ke karyawan.
    // Otomatis menutup assignment aktif sebelumnya jika ada.
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Cek tidak ada overlap dengan assignment lain di karyawan yang sama
        $overlap = $this->cekOverlap(
            employeeId: $data['employee_id'],
            tanggalMulai: $data['tanggal_mulai'],
            tanggalSelesai: $data['tanggal_selesai'] ?? null,
        );

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal assignment overlap dengan assignment shift lain yang sudah ada.',
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Tutup assignment aktif sebelumnya yang belum ada tanggal selesai
            // atau yang tanggal_selesai-nya lebih besar dari tanggal_mulai baru - 1 hari
            $sebelumnya = EmployeeShift::where('employee_id', $data['employee_id'])
                ->where(function ($q) use ($data) {
                    $q->whereNull('tanggal_selesai')
                        ->orWhere('tanggal_selesai', '>=', $data['tanggal_mulai']);
                })
                ->where('tanggal_mulai', '<', $data['tanggal_mulai'])
                ->get();

            foreach ($sebelumnya as $lama) {
                // Selesaikan 1 hari sebelum shift baru mulai
                $batasAkhir = date('Y-m-d', strtotime($data['tanggal_mulai'].' -1 day'));
                $lama->update(['tanggal_selesai' => $batasAkhir]);
            }

            $employeeShift = EmployeeShift::create($data);
            $employeeShift->load([
                'employee:id,full_name,employee_code,photo_url',
                'shift:id,nama,kode,jam_masuk,jam_pulang,melewati_tengah_malam',
                'pattern:id,nama,kode',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil di-assign ke karyawan.',
            'data' => $this->formatItem($employeeShift),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /admin/employee-shifts/{employeeShift}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, EmployeeShift $employeeShift): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules($employeeShift->id));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Cek overlap kecuali diri sendiri
        $overlap = $this->cekOverlap(
            employeeId: $data['employee_id'],
            tanggalMulai: $data['tanggal_mulai'],
            tanggalSelesai: $data['tanggal_selesai'] ?? null,
            ignoreId: $employeeShift->id,
        );

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal assignment overlap dengan assignment shift lain yang sudah ada.',
            ], 409);
        }

        $employeeShift->update($data);
        $employeeShift->load([
            'employee:id,full_name,employee_code,photo_url',
            'shift:id,nama,kode,jam_masuk,jam_pulang,melewati_tengah_malam',
            'pattern:id,nama,kode',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment shift berhasil diperbarui.',
            'data' => $this->formatItem($employeeShift),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /admin/employee-shifts/{employeeShift}
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(EmployeeShift $employeeShift): JsonResponse
    {
        $employeeShift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment shift berhasil dihapus.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Flatten data relasi agar response lebih mudah diparse Flutter.
     */
    private function formatItem(EmployeeShift $es): array
    {
        return [
            'id' => $es->id,
            'employee_id' => $es->employee_id,
            'shift_id' => $es->shift_id,
            'tanggal_mulai' => $es->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $es->tanggal_selesai?->toDateString(),
            'keterangan' => $es->keterangan,
            'created_at' => $es->created_at?->toDateTimeString(),
            'updated_at' => $es->updated_at?->toDateTimeString(),

            // Employee fields
            'employee_name' => $es->employee?->full_name,
            'employee_code' => $es->employee?->employee_code,
            'employee_photo' => $es->employee?->photo_url,

            // Shift fields
            'shift_nama' => $es->shift?->nama,
            'shift_kode' => $es->shift?->kode,
            'jam_masuk' => $es->shift?->jam_masuk,
            'jam_pulang' => $es->shift?->jam_pulang,
            'melewati_tengah_malam' => $es->shift?->melewati_tengah_malam,

            // Nested objects (untuk backward-compat)
            'employee' => $es->employee ? [
                'id' => $es->employee->id,
                'full_name' => $es->employee->full_name,
                'employee_code' => $es->employee->employee_code,
                'photo_url' => $es->employee->photo_url,
            ] : null,
            'shift' => $es->shift ? [
                'id' => $es->shift->id,
                'nama' => $es->shift->nama,
                'kode' => $es->shift->kode,
                'jam_masuk' => $es->shift->jam_masuk,
                'jam_pulang' => $es->shift->jam_pulang,
                'melewati_tengah_malam' => $es->shift->melewati_tengah_malam,
            ] : null,
            'pattern_id' => $es->pattern_id,
            'pattern_nama' => $es->pattern?->nama,
            'pattern_kode' => $es->pattern?->kode,
            'pattern' => $es->pattern ? [
                'id' => $es->pattern->id,
                'nama' => $es->pattern->nama,
                'kode' => $es->pattern->kode,
            ] : null,
        ];
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')],
            'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $inserted = 0;
        $skipped = 0;

        foreach ($data['employee_ids'] as $employeeId) {
            // Cek apakah sudah ada assignment aktif untuk shift yang sama
            $exists = EmployeeShift::where('employee_id', $employeeId)
                ->where('shift_id', $data['shift_id'])
                ->where(function ($q) use ($data) {
                    $q->whereNull('tanggal_selesai')
                        ->orWhere('tanggal_selesai', '>=', $data['tanggal_mulai']);
                })
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            EmployeeShift::create([
                'employee_id' => $employeeId,
                'shift_id' => $data['shift_id'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            $inserted++;
        }

        return response()->json([
            'success' => true,
            'message' => "$inserted karyawan berhasil di-assign, $skipped dilewati (sudah ada).",
            'data' => ['inserted' => $inserted, 'skipped' => $skipped],
        ]);
    }

    /**
     * Cek apakah rentang tanggal baru overlap dengan assignment yang sudah ada
     * pada karyawan yang sama.
     *
     * Logika overlap dua rentang [A, B] dan [C, D]:
     *   overlap jika A <= D && C <= B
     *   (null tanggal_selesai = tak terbatas = "9999-12-31")
     */
    private function cekOverlap(
        int $employeeId,
        string $tanggalMulai,
        ?string $tanggalSelesai,
        ?int $ignoreId = null,
    ): bool {
        $endBaru = $tanggalSelesai ?? '9999-12-31';

        $query = EmployeeShift::where('employee_id', $employeeId)
            ->where(function ($q) use ($tanggalMulai, $endBaru) {
                // Start yang ada <= End baru
                $q->where('tanggal_mulai', '<=', $endBaru)
                  // End yang ada >= Start baru
                    ->where(function ($q2) use ($tanggalMulai) {
                        $q2->whereNull('tanggal_selesai')
                            ->orWhere('tanggal_selesai', '>=', $tanggalMulai);
                    });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Validation rules untuk store & update.
     * $ignoreId dipakai saat update agar unique check mengabaikan record sendiri.
     */
    private function rules(?int $ignoreId = null): array
    {
        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],
            // shift_id ATAU pattern_id — salah satu wajib diisi
            'shift_id' => [
                'nullable', 'integer',
                Rule::exists('shifts', 'id')->whereNull('deleted_at'),
                // Wajib jika pattern_id kosong
                Rule::requiredIf(fn () => ! request()->filled('pattern_id')),
            ],
            'pattern_id' => [
                'nullable', 'integer',
                Rule::exists('shift_weekly_patterns', 'id')->whereNull('deleted_at'),
                Rule::requiredIf(fn () => ! request()->filled('shift_id')),
            ],
            'tanggal_mulai' => ['required', 'date', 'date_format:Y-m-d'],
            'tanggal_selesai' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
