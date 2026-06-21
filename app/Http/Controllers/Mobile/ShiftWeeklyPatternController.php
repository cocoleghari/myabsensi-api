<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ShiftWeeklyPattern;
use App\Models\ShiftWeeklyPatternDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShiftWeeklyPatternController extends Controller
{
    // GET /admin/shift-patterns
    public function index(Request $request): JsonResponse
    {
        $query = ShiftWeeklyPattern::with('company:id,name')
            ->withCount('days');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('nama', 'like', "%{$q}%")
                ->orWhere('kode', 'like', "%{$q}%"));
        }

        $perPage = (int) $request->get('per_page', 20);
        $results = $query->orderBy('nama')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    // GET /admin/shift-patterns-list  (dropdown)
    public function list(): JsonResponse
    {
        $patterns = ShiftWeeklyPattern::where('is_active', true)
            ->select('id', 'nama', 'kode', 'company_id')
            ->with('company:id,name')
            ->orderBy('nama')
            ->get();

        return response()->json(['success' => true, 'data' => $patterns]);
    }

    // GET /admin/shift-patterns/{pattern}
    public function show(ShiftWeeklyPattern $pattern): JsonResponse
    {
        $pattern->load([
            'company:id,name',
            'days.shift:id,nama,kode,jam_masuk,jam_pulang',
        ]);

        // Kembalikan semua 7 hari — isi dengan data atau default
        $days = collect(ShiftWeeklyPattern::HARI_LABELS)
            ->map(function ($label, $hari) use ($pattern) {
                $day = $pattern->days->firstWhere('hari', $hari);

                return [
                    'hari' => $hari,
                    'hari_label' => $label,
                    'day_id' => $day?->id,
                    'shift_id' => $day?->shift_id,
                    'shift_nama' => $day?->shift?->nama,
                    'shift_kode' => $day?->shift?->kode,
                    'jam_masuk' => $day?->shift?->jam_masuk,
                    'jam_pulang' => $day?->shift?->jam_pulang,
                    'is_libur' => $day?->is_libur ?? false,
                    'keterangan' => $day?->keterangan,
                ];
            })->values();

        return response()->json([
            'success' => true,
            'data' => array_merge($pattern->toArray(), ['days' => $days]),
        ]);
    }

    // POST /admin/shift-patterns
    // Body: { nama, kode, company_id, keterangan, is_active, days: [...] }
    // days: array 7 item — { hari: 0-6, shift_id: int|null, is_libur: bool }
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->headerRules());
        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $daysError = $this->validateDays($request->input('days', []));
        if ($daysError) {
            return response()->json(['success' => false, 'message' => $daysError], 422);
        }

        DB::beginTransaction();
        try {
            $pattern = ShiftWeeklyPattern::create($validator->validated());
            $this->syncDays($pattern, $request->input('days', []));
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $pattern->load(['company:id,name', 'days.shift:id,nama,kode,jam_masuk,jam_pulang']);

        return response()->json([
            'success' => true,
            'message' => 'Pola shift mingguan berhasil dibuat.',
            'data' => $pattern,
        ], 201);
    }

    // PUT /admin/shift-patterns/{pattern}
    public function update(Request $request, ShiftWeeklyPattern $pattern): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->headerRules($pattern->id));
        if ($validator->fails()) {
            return response()->json([
                'success' => false, 'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $daysError = $this->validateDays($request->input('days', []));
        if ($daysError) {
            return response()->json(['success' => false, 'message' => $daysError], 422);
        }

        DB::beginTransaction();
        try {
            $pattern->update($validator->validated());
            if ($request->has('days')) {
                $this->syncDays($pattern, $request->input('days'));
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $pattern->load(['company:id,name', 'days.shift:id,nama,kode,jam_masuk,jam_pulang']);

        return response()->json([
            'success' => true,
            'message' => 'Pola shift mingguan berhasil diperbarui.',
            'data' => $pattern,
        ]);
    }

    // DELETE /admin/shift-patterns/{pattern}
    public function destroy(ShiftWeeklyPattern $pattern): JsonResponse
    {
        $aktif = $pattern->employeeShifts()
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')
                ->orWhere('tanggal_selesai', '>=', now()->toDateString()))
            ->exists();

        if ($aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Pola tidak dapat dihapus karena masih digunakan karyawan.',
            ], 409);
        }

        $pattern->delete();

        return response()->json(['success' => true, 'message' => 'Pola shift berhasil dihapus.']);
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function headerRules(?int $ignoreId = null): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'nama' => ['required', 'string', 'max:100'],
            'kode' => [
                'required', 'string', 'max:20',
                Rule::unique('shift_weekly_patterns', 'kode')
                    ->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    private function validateDays(array $days): ?string
    {
        if (empty($days)) {
            return null;
        }

        $hariSudahAda = [];
        foreach ($days as $i => $day) {
            $hari = $day['hari'] ?? null;
            if (! is_int($hari) || $hari < 0 || $hari > 6) {
                return "days[$i].hari harus integer 0–6.";
            }
            if (in_array($hari, $hariSudahAda)) {
                return 'Hari '.ShiftWeeklyPattern::HARI_LABELS[$hari].' duplikat dalam array days.';
            }
            $hariSudahAda[] = $hari;

            $isLibur = $day['is_libur'] ?? false;
            if (! $isLibur && empty($day['shift_id'])) {
                return "days[$i]: shift_id wajib diisi jika bukan hari libur.";
            }
        }

        return null;
    }

    // upsert + hapus hari yang tidak dikirim
    private function syncDays(ShiftWeeklyPattern $pattern, array $days): void
    {
        $hariDikirim = [];

        foreach ($days as $day) {
            $hariDikirim[] = $day['hari'];
            ShiftWeeklyPatternDay::updateOrCreate(
                ['pattern_id' => $pattern->id, 'hari' => $day['hari']],
                [
                    'shift_id' => $day['is_libur'] ? null : ($day['shift_id'] ?? null),
                    'is_libur' => $day['is_libur'] ?? false,
                    'keterangan' => $day['keterangan'] ?? null,
                ]
            );
        }

        // Hapus hari yang tidak dikirim (misal: user hapus konfigurasi Sabtu)
        if (! empty($hariDikirim)) {
            ShiftWeeklyPatternDay::where('pattern_id', $pattern->id)
                ->whereNotIn('hari', $hariDikirim)
                ->delete();
        }
    }
}
