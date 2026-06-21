<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShiftController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/shifts-list
    // Untuk dropdown di Flutter (hanya shift aktif)
    // ─────────────────────────────────────────────────────────────────────────

    public function list(): JsonResponse
    {
        $shifts = Shift::where('is_active', true)
            ->select('id', 'nama', 'kode', 'jam_masuk', 'jam_pulang', 'company_id')
            ->with('company:id,name')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/shifts
    // Daftar shift dengan filter & paginasi
    // Query params: company_id, is_active, search, per_page
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Shift::with('company:id,name')
            ->withCount('employeeShifts');

        // Filter company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter status aktif
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Pencarian nama / kode
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('nama', 'like', "%{$q}%")
                    ->orWhere('kode', 'like', "%{$q}%");
            });
        }

        $perPage = (int) $request->get('per_page', 20);
        $shifts = $query->orderBy('nama')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $shifts->items(),
            'meta' => [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/shifts/{shift}
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Shift $shift): JsonResponse
    {
        $shift->load('company:id,name');

        return response()->json([
            'success' => true,
            'data' => $shift,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/shifts
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

        $shift = Shift::create($validator->validated());
        $shift->load('company:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dibuat.',
            'data' => $shift,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /admin/shifts/{shift}
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules($shift->id));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shift->update($validator->validated());
        $shift->load('company:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil diperbarui.',
            'data' => $shift,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /admin/shifts/{shift}
    // Tidak bisa hapus jika masih ada assignment aktif
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(Shift $shift): JsonResponse
    {
        // Cek apakah masih ada assignment aktif (tanggal_selesai null atau >= hari ini)
        $aktif = $shift->employeeShifts()
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
            ->exists();

        if ($aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Shift tidak dapat dihapus karena masih ada karyawan yang menggunakan shift ini.',
            ], 409);
        }

        $shift->delete(); // SoftDelete (pakai SoftDeletes pada model)

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dihapus.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDATION RULES
    // ─────────────────────────────────────────────────────────────────────────

    private function rules(?int $ignoreId = null): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id'),
            ],
            'nama' => [
                'required',
                'string',
                'max:100',
            ],
            'kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('shifts', 'kode')
                    ->ignore($ignoreId)
                    ->whereNull('deleted_at'),
            ],
            'jam_masuk' => ['required', 'date_format:H:i,G:i'],
            'jam_pulang' => ['required', 'date_format:H:i,G:i'],
            'toleransi_terlambat_menit' => [
                'required',
                'integer',
                'min:0',
                'max:240',
            ],
            'window_masuk_awal_menit' => [
                'required',
                'integer',
                'min:0',
                'max:240',
            ],
            'melewati_tengah_malam' => [
                'boolean',
            ],
            'batas_waktu_pulang' => ['required', 'date_format:H:i,G:i'],
            'berlaku_hari_libur' => [
                'boolean',
            ],
            'berlaku_akhir_pekan' => [
                'boolean',
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:500',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * GET /admin/shifts/export
     */
    public function export(Request $request): StreamedResponse
    {
        $shifts = Shift::with('company:id,name')
            ->withCount('employeeShifts')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->orderBy('nama')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Shifts');

        $headers = [
            'A1' => 'ID',
            'B1' => 'Nama Shift',
            'C1' => 'Kode',
            'D1' => 'Company',
            'E1' => 'Jam Masuk',
            'F1' => 'Jam Pulang',
            'G1' => 'Toleransi Terlambat (menit)',
            'H1' => 'Window Masuk Awal (menit)',
            'I1' => 'Melewati Tengah Malam',
            'J1' => 'Batas Waktu Pulang',
            'K1' => 'Berlaku Hari Libur',
            'L1' => 'Berlaku Akhir Pekan',
            'M1' => 'Keterangan',
            'N1' => 'Status',
            'O1' => 'Jumlah Assignment',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0277BD']], // biru
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($shifts as $shift) {
            $sheet->setCellValue("A{$row}", $shift->id);
            $sheet->setCellValue("B{$row}", $shift->nama);
            $sheet->setCellValue("C{$row}", $shift->kode ?? '');
            $sheet->setCellValue("D{$row}", $shift->company?->name ?? '');
            $sheet->setCellValue("E{$row}", $shift->jam_masuk);
            $sheet->setCellValue("F{$row}", $shift->jam_pulang);
            $sheet->setCellValue("G{$row}", $shift->toleransi_terlambat_menit);
            $sheet->setCellValue("H{$row}", $shift->window_masuk_awal_menit);
            $sheet->setCellValue("I{$row}", $shift->melewati_tengah_malam ? 'Ya' : 'Tidak');
            $sheet->setCellValue("J{$row}", $shift->batas_waktu_pulang);
            $sheet->setCellValue("K{$row}", $shift->berlaku_hari_libur ? 'Ya' : 'Tidak');
            $sheet->setCellValue("L{$row}", $shift->berlaku_akhir_pekan ? 'Ya' : 'Tidak');
            $sheet->setCellValue("M{$row}", $shift->keterangan ?? '');
            $sheet->setCellValue("N{$row}", $shift->is_active ? 'Aktif' : 'Nonaktif');
            $sheet->setCellValue("O{$row}", $shift->employee_shifts_count);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
                ]);
            }
            $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(28);
        $sheet->getColumnDimension('H')->setWidth(26);
        $sheet->getColumnDimension('I')->setWidth(22);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(20);
        $sheet->getColumnDimension('L')->setWidth(20);
        $sheet->getColumnDimension('M')->setWidth(30);
        $sheet->getColumnDimension('N')->setWidth(12);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->freezePane('A2');

        // Sheet petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk Import');
        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN IMPORT SHIFT');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0277BD']],
        ]);

        $guideRows = [
            ['Kolom', 'Keterangan', 'Wajib?', 'Contoh'],
            ['nama', 'Nama shift', 'Ya', 'Shift Pagi'],
            ['kode', 'Kode unik shift', 'Ya', 'PAGI'],
            ['company_id', 'ID company', 'Ya', '1'],
            ['jam_masuk', 'Format H:i (24 jam)', 'Ya', '7:30'],
            ['jam_pulang', 'Format H:i (24 jam)', 'Ya', '16:00'],
            ['toleransi_terlambat_menit', 'Menit toleransi keterlambatan (0-240)', 'Ya', '15'],
            ['window_masuk_awal_menit', 'Menit sebelum jam masuk boleh absen (0-240)', 'Ya', '30'],
            ['melewati_tengah_malam', '1 = Ya, 0 = Tidak', 'Tidak', '0'],
            ['batas_waktu_pulang', 'Format H:i (24 jam)', 'Ya', '23:00'],
            ['berlaku_hari_libur', '1 = Ya, 0 = Tidak', 'Tidak', '0'],
            ['berlaku_akhir_pekan', '1 = Ya, 0 = Tidak', 'Tidak', '0'],
            ['keterangan', 'Keterangan opsional', 'Tidak', 'Shift reguler pagi'],
            ['is_active', '1 = Aktif, 0 = Nonaktif', 'Tidak', '1'],
        ];

        foreach ($guideRows as $i => $cols) {
            $r = $i + 3;
            $guide->setCellValue("A{$r}", $cols[0]);
            $guide->setCellValue("B{$r}", $cols[1]);
            $guide->setCellValue("C{$r}", $cols[2]);
            $guide->setCellValue("D{$r}", $cols[3]);

            if ($i === 0) {
                $guide->getStyle("A{$r}:D{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0277BD']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
        $guide->getColumnDimension('A')->setWidth(28);
        $guide->getColumnDimension('B')->setWidth(50);
        $guide->getColumnDimension('C')->setWidth(12);
        $guide->getColumnDimension('D')->setWidth(25);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'shifts_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * GET /admin/shifts/import-template
     */
    public function importTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Shifts');

        $headers = [
            'nama', 'kode', 'company_id',
            'jam_masuk', 'jam_pulang',
            'toleransi_terlambat_menit', 'window_masuk_awal_menit',
            'melewati_tengah_malam', 'batas_waktu_pulang',
            'berlaku_hari_libur', 'berlaku_akhir_pekan',
            'keterangan', 'is_active',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i).'1', $h);
        }

        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0277BD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Contoh baris
        $sheet->setCellValue('A2', 'Shift Pagi');
        $sheet->setCellValue('B2', 'PAGI');
        $sheet->setCellValue('C2', '1');
        $sheet->setCellValue('D2', '7:30');
        $sheet->setCellValue('E2', '16:00');
        $sheet->setCellValue('F2', '15');
        $sheet->setCellValue('G2', '30');
        $sheet->setCellValue('H2', '0');
        $sheet->setCellValue('I2', '23:00');
        $sheet->setCellValue('J2', '0');
        $sheet->setCellValue('K2', '0');
        $sheet->setCellValue('L2', 'Shift reguler pagi');
        $sheet->setCellValue('M2', '1');

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template_import_shifts.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * POST /admin/shifts/import
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $reader = new XlsxReader;
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $dataRows = array_slice($rows, 1);

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($dataRows as $i => $row) {
                $rowNum = $i + 2;

                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                [
                    $nama, $kode, $companyId,
                    $jamMasuk, $jamPulang,
                    $toleransi, $window,
                    $melewatiTengahMalam, $batasWaktuPulang,
                    $berlakuHariLibur, $berlakuAkhirPekan,
                    $keterangan, $isActive,
                ] = array_pad($row, 13, null);

                // Validasi per baris
                $rowErrors = [];
                if (empty(trim((string) $nama))) {
                    $rowErrors[] = 'nama wajib diisi';
                }
                if (empty(trim((string) $kode))) {
                    $rowErrors[] = 'kode wajib diisi';
                }
                if (empty($companyId) || ! Company::find((int) $companyId)) {
                    $rowErrors[] = 'company_id tidak valid';
                }
                if (empty(trim((string) $jamMasuk))) {
                    $rowErrors[] = 'jam_masuk wajib diisi';
                }
                if (empty(trim((string) $jamPulang))) {
                    $rowErrors[] = 'jam_pulang wajib diisi';
                }
                if (empty(trim((string) $batasWaktuPulang))) {
                    $rowErrors[] = 'batas_waktu_pulang wajib diisi';
                }
                if (! is_numeric($toleransi) || (int) $toleransi < 0 || (int) $toleransi > 240) {
                    $rowErrors[] = 'toleransi_terlambat_menit harus angka 0-240';
                }
                if (! is_numeric($window) || (int) $window < 0 || (int) $window > 240) {
                    $rowErrors[] = 'window_masuk_awal_menit harus angka 0-240';
                }

                if (! empty($rowErrors)) {
                    $errors[] = "Baris {$rowNum}: ".implode(', ', $rowErrors);
                    $failed++;

                    continue;
                }

                // Cek duplikat kode per company
                $exists = Shift::where('company_id', (int) $companyId)
                    ->where('kode', strtoupper(trim((string) $kode)))
                    ->exists();
                if ($exists) {
                    $errors[] = "Baris {$rowNum}: kode '{$kode}' sudah dipakai di company ini";
                    $failed++;

                    continue;
                }

                try {
                    Shift::create([
                        'nama' => trim((string) $nama),
                        'kode' => strtoupper(trim((string) $kode)),
                        'company_id' => (int) $companyId,
                        'jam_masuk' => trim((string) $jamMasuk),
                        'jam_pulang' => trim((string) $jamPulang),
                        'toleransi_terlambat_menit' => (int) $toleransi,
                        'window_masuk_awal_menit' => (int) $window,
                        'melewati_tengah_malam' => in_array((string) $melewatiTengahMalam, ['1', 'true', 'ya'], true),
                        'batas_waktu_pulang' => trim((string) $batasWaktuPulang),
                        'berlaku_hari_libur' => in_array((string) $berlakuHariLibur, ['1', 'true', 'ya'], true),
                        'berlaku_akhir_pekan' => in_array((string) $berlakuAkhirPekan, ['1', 'true', 'ya'], true),
                        'keterangan' => trim((string) ($keterangan ?? '')),
                        'is_active' => in_array((string) $isActive, ['1', 'true', 'aktif', 'active'], true),
                    ]);
                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = "Baris {$rowNum}: ".$e->getMessage();
                    $failed++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Import selesai: {$success} berhasil, {$failed} gagal.",
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Shift import error: '.$e->getMessage());

            return response()->json(['message' => 'Gagal membaca file: '.$e->getMessage()], 500);
        }
    }
}
