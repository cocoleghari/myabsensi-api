<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Shift;
use App\Models\ShiftWeeklyPattern;
use App\Models\ShiftWeeklyPatternDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShiftWeeklyPatternController extends Controller
{
    public function index(Request $request)
    {
        $query = ShiftWeeklyPattern::with('company:id,name')
            ->withCount(['days', 'employeeShifts']);

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

        $patterns = $query->orderBy('nama')->paginate(15)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.pola-shift', compact('patterns', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $shifts = Shift::where('is_active', true)
            ->select('id', 'nama', 'kode', 'jam_masuk', 'jam_pulang', 'company_id')
            ->orderBy('nama')
            ->get();
        $hariLabels = ShiftWeeklyPattern::HARI_LABELS;

        return view('admin.pola-shift-form', compact('companies', 'shifts', 'hariLabels'));
    }

    public function store(Request $request)
    {
        $request->merge(['kode' => strtoupper(trim($request->kode ?? ''))]);
        $data = $request->validate($this->headerRules());

        $days = $this->parseDaysFromRequest($request);
        $daysError = $this->validateDays($days);
        if ($daysError) {
            return back()->withInput()->with('error', $daysError);
        }

        DB::transaction(function () use ($data, $request, $days) {
            $pattern = ShiftWeeklyPattern::create([
                'company_id' => $data['company_id'],
                'nama' => $data['nama'],
                'kode' => $data['kode'],
                'keterangan' => $data['keterangan'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncDays($pattern, $days);
        });

        return redirect()->route('admin.pola-shift.index')->with('success', 'Pola shift mingguan berhasil dibuat.');
    }

    public function edit(ShiftWeeklyPattern $pattern)
    {
        $pattern->load('days.shift:id,nama,kode,jam_masuk,jam_pulang');

        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $shifts = Shift::where('is_active', true)
            ->select('id', 'nama', 'kode', 'jam_masuk', 'jam_pulang', 'company_id')
            ->orderBy('nama')
            ->get();
        $hariLabels = ShiftWeeklyPattern::HARI_LABELS;

        $daysData = collect($hariLabels)->map(function ($label, $hari) use ($pattern) {
            $day = $pattern->days->firstWhere('hari', $hari);

            return [
                'hari' => $hari,
                'shift_id' => $day?->shift_id,
                'is_libur' => $day?->is_libur ?? false,
                'keterangan' => $day?->keterangan,
            ];
        })->values();

        return view('admin.pola-shift-form', compact('pattern', 'companies', 'shifts', 'hariLabels', 'daysData'));
    }

    public function update(Request $request, ShiftWeeklyPattern $pattern)
    {
        $request->merge(['kode' => strtoupper(trim($request->kode ?? ''))]);
        $data = $request->validate($this->headerRules($pattern->id));

        $days = $this->parseDaysFromRequest($request);
        $daysError = $this->validateDays($days);
        if ($daysError) {
            return back()->withInput()->with('error', $daysError);
        }

        DB::transaction(function () use ($data, $request, $days, $pattern) {
            $pattern->update([
                'company_id' => $data['company_id'],
                'nama' => $data['nama'],
                'kode' => $data['kode'],
                'keterangan' => $data['keterangan'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncDays($pattern, $days);
        });

        return redirect()->route('admin.pola-shift.index')->with('success', 'Pola shift mingguan berhasil diperbarui.');
    }

    public function destroy(ShiftWeeklyPattern $pattern)
    {
        $aktif = $pattern->employeeShifts()
            ->where(fn ($q) => $q->whereNull('tanggal_selesai')
                ->orWhere('tanggal_selesai', '>=', now()->toDateString()))
            ->exists();

        if ($aktif) {
            return redirect()->route('admin.pola-shift.index')
                ->with('error', 'Pola tidak dapat dihapus karena masih digunakan karyawan.');
        }

        $pattern->delete();

        return redirect()->route('admin.pola-shift.index')->with('success', 'Pola shift mingguan berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    private function headerRules(?int $ignoreId = null): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'nama' => 'required|string|max:100',
            'kode' => [
                'required', 'string', 'max:20',
                Rule::unique('shift_weekly_patterns', 'kode')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    private function parseDaysFromRequest(Request $request): array
    {
        $days = [];
        foreach (range(0, 6) as $hari) {
            $isLibur = $request->boolean("days.{$hari}.is_libur");
            $days[] = [
                'hari' => $hari,
                'shift_id' => $isLibur ? null : $request->input("days.{$hari}.shift_id"),
                'is_libur' => $isLibur,
                'keterangan' => $request->input("days.{$hari}.keterangan"),
            ];
        }

        return $days;
    }

    private function validateDays(array $days): ?string
    {
        foreach ($days as $i => $day) {
            if (! $day['is_libur'] && empty($day['shift_id'])) {
                $label = ShiftWeeklyPattern::HARI_LABELS[$day['hari']] ?? "Hari {$i}";

                return "{$label}: pilih shift atau tandai sebagai libur.";
            }
        }

        return null;
    }

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

        if (! empty($hariDikirim)) {
            ShiftWeeklyPatternDay::where('pattern_id', $pattern->id)
                ->whereNotIn('hari', $hariDikirim)
                ->delete();
        }
    }
}
