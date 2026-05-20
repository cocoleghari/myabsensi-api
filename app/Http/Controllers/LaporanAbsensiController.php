<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanAbsensiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/laporan-absensi/summary
    // Preview ringkasan data sebelum di-export (dipakai oleh halaman Flutter)
    // ─────────────────────────────────────────────────────────────────────────
    public function summary(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'pusat_lokasi_id' => 'nullable|integer|exists:pusat_lokasis,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'status' => 'nullable|in:tepat_waktu,terlambat,diluar_lokasi,lembur',
            'tipe_absen' => 'nullable|in:masuk,pulang',
        ]);

        $query = $this->buildQuery($request);

        // Hitung statistik
        $totalAbsensi = (clone $query)->count();
        $totalKaryawan = (clone $query)->distinct('employee_id')->count('employee_id');
        $tepatWaktu = (clone $query)->where('status', 'tepat_waktu')->count();
        $terlambat = (clone $query)->where('status', 'terlambat')->count();
        $diluarLokasi = (clone $query)->where('status', 'diluar_lokasi')->count();
        $lembur = (clone $query)->where('status', 'lembur')->count();
        $rataMenitTerlambat = (clone $query)->where('menit_terlambat', '>', 0)->avg('menit_terlambat');
        $rataMenitLembur = (clone $query)->where('menit_lembur', '>', 0)->avg('menit_lembur');

        return response()->json([
            'total_absensi' => $totalAbsensi,
            'total_karyawan' => $totalKaryawan,
            'tepat_waktu' => $tepatWaktu,
            'terlambat' => $terlambat,
            'diluar_lokasi' => $diluarLokasi,
            'lembur' => $lembur,
            'rata_menit_terlambat' => round($rataMenitTerlambat ?? 0, 1),
            'rata_menit_lembur' => round($rataMenitLembur ?? 0, 1),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/laporan-absensi/export
    // Generate & stream file Excel (.xlsx)
    // Query params (semua opsional kecuali tanggal):
    //   tanggal_mulai, tanggal_selesai, employee_id, pusat_lokasi_id,
    //   department_id, status, tipe_absen, format (detail|rekap)
    // ─────────────────────────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'pusat_lokasi_id' => 'nullable|integer|exists:pusat_lokasis,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'status' => 'nullable|in:tepat_waktu,terlambat,diluar_lokasi,lembur',
            'tipe_absen' => 'nullable|in:masuk,pulang',
            'format' => 'nullable|in:detail,rekap',
        ]);

        $format = $request->input('format', 'detail');

        $spreadsheet = $format === 'rekap'
            ? $this->buildRekapSheet($request)
            : $this->buildDetailSheet($request);

        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $filename = "laporan_absensi_{$tanggalMulai}_sd_{$tanggalSelesai}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    // =========================================================================
    // PRIVATE — BUILD QUERY
    // =========================================================================

    private function buildQuery(Request $request)
    {
        $query = Absensi::query()
            ->with([
                'employee:id,employee_code,full_name,nik,department_id,position_id',
                'employee.department:id,name',
                'employee.position:id,name,job_grade_id',
                'employee.position.jobGrade:id,name,code',
                'pusatLokasi:id,nama_lokasi',
                'shift:id,nama',
            ])
            ->whereBetween('tanggal_absen', [
                $request->input('tanggal_mulai'),
                $request->input('tanggal_selesai'),
            ])
            ->orderBy('tanggal_absen')
            ->orderBy('waktu_absen');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('pusat_lokasi_id')) {
            $query->where('pusat_lokasi_id', $request->input('pusat_lokasi_id'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->input('department_id'))
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('tipe_absen')) {
            $query->where('tipe_absen', $request->input('tipe_absen'));
        }

        return $query;
    }

    // =========================================================================
    // PRIVATE — SHEET DETAIL (1 baris per record absensi)
    // =========================================================================

    private function buildDetailSheet(Request $request): Spreadsheet
    {
        $records = $this->buildQuery($request)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Absensi');

        // ── Metadata perusahaan ──────────────────────────────────────────────
        $this->writeMetaHeader($sheet, $request, 'LAPORAN ABSENSI DETAIL', 13);

        // ── Header tabel ─────────────────────────────────────────────────────
        $headerRow = 7;
        $headers = [
            'A' => ['No',               5],
            'B' => ['Nama Karyawan',   26],
            'C' => ['Jabatan',         20],
            'D' => ['Department',      20],
            'E' => ['Tanggal',         13],
            'F' => ['Waktu Absen',     13],
            'G' => ['Tipe Absen',      12],
            'H' => ['Lokasi',          22],
            'I' => ['Status',          15],
            'J' => ['Menit Terlambat', 16],
            'K' => ['Menit Lembur',    14],
            'L' => ['Jarak (m)',       12],
            'M' => ['Catatan',         28],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $cell = $col.$headerRow;
            $sheet->setCellValue($cell, $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $this->applyHeaderStyle($sheet, "A{$headerRow}:M{$headerRow}");

        // ── Data ─────────────────────────────────────────────────────────────
        $row = $headerRow + 1;
        $no = 1;

        foreach ($records as $rec) {
            $tanggal = Carbon::parse($rec->tanggal_absen);
            $waktu = Carbon::parse($rec->waktu_absen)->format('H:i:s');

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $rec->employee?->full_name ?? '-');
            $sheet->setCellValue("C{$row}", $rec->employee?->position?->jobGrade?->name ?? '-');
            $sheet->setCellValue("D{$row}", $rec->employee?->department?->name ?? '-');
            $sheet->setCellValue("E{$row}", $tanggal->format('d/m/Y'));
            $sheet->setCellValue("F{$row}", $waktu);
            $sheet->setCellValue("G{$row}", ucfirst($rec->tipe_absen));
            $sheet->setCellValue("H{$row}", $rec->pusatLokasi?->nama_lokasi ?? '-');
            $sheet->setCellValue("I{$row}", $this->labelStatus($rec->status));
            $sheet->setCellValue("J{$row}", $rec->menit_terlambat ?? 0);
            $sheet->setCellValue("K{$row}", $rec->menit_lembur ?? 0);
            $sheet->setCellValue("L{$row}", $rec->jarak_meter ?? '-');
            $sheet->setCellValue("M{$row}", $rec->catatan ?? '');

            // Warna baris berdasarkan status
            $this->applyRowStatusStyle($sheet, $row, $rec->status, 'A', 'M');

            $row++;
        }

        // ── Garis luar tabel ─────────────────────────────────────────────────
        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            $this->applyOuterBorder($sheet, "A{$headerRow}:M{$lastDataRow}");
        }

        // ── Sheet ringkasan (tab kedua) ───────────────────────────────────────
        $this->addSummaryTab($spreadsheet, $records, $request);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // =========================================================================
    // PRIVATE — SHEET REKAP (1 baris per karyawan per hari)
    // =========================================================================

    private function buildRekapSheet(Request $request): Spreadsheet
    {
        $records = $this->buildQuery($request)->get();

        // Kelompokkan: employee_id → tanggal → [masuk, pulang]
        $grouped = [];
        foreach ($records as $rec) {
            $empId = $rec->employee_id ?? null;
            $tanggal = is_string($rec->tanggal_absen)
                ? $rec->tanggal_absen
                : (string) $rec->tanggal_absen;
            $tipe = is_string($rec->tipe_absen) ? trim(strtolower($rec->tipe_absen)) : null;

            // Skip jika key tidak valid
            if (empty($empId) || empty($tanggal) || ! in_array($tipe, ['masuk', 'pulang'])) {
                continue;
            }

            if (! isset($grouped[$empId])) {
                $grouped[$empId] = [
                    'employee' => $rec->employee,
                    'dates' => [],
                ];
            }
            if (! isset($grouped[$empId]['dates'][$tanggal])) {
                $grouped[$empId]['dates'][$tanggal] = ['masuk' => null, 'pulang' => null];
            }
            $grouped[$empId]['dates'][$tanggal][$tipe] = $rec;
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Absensi');

        $this->writeMetaHeader($sheet, $request, 'REKAP ABSENSI KARYAWAN', 13);

        $headerRow = 7;
        $headers = [
            'A' => ['No',               5],
            'B' => ['Nama Karyawan',   26],
            'C' => ['Jabatan',         20],
            'D' => ['Department',      20],
            'E' => ['Tanggal',         13],
            'F' => ['Jam Masuk',       12],
            'G' => ['Jam Pulang',      12],
            'H' => ['Tipe Absen',      12],
            'I' => ['Lokasi',          22],
            'J' => ['Status',          15],
            'K' => ['Menit Terlambat', 16],
            'L' => ['Menit Lembur',    14],
            'M' => ['Jarak (m)',       12],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue($col.$headerRow, $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $this->applyHeaderStyle($sheet, "A{$headerRow}:M{$headerRow}");

        $row = $headerRow + 1;
        $no = 1;

        foreach ($grouped as $empData) {
            ksort($empData['dates']); // urutkan tanggal
            $emp = $empData['employee'];

            foreach ($empData['dates'] as $tanggal => $absensi) {
                $tgl = Carbon::parse($tanggal);
                $masuk = $absensi['masuk'];
                $pulang = $absensi['pulang'];

                $jamMasuk = $masuk ? Carbon::parse($masuk->waktu_absen)->format('H:i') : '-';
                $jamPulang = $pulang ? Carbon::parse($pulang->waktu_absen)->format('H:i') : '-';

                $sheet->setCellValue("A{$row}", $no++);
                $sheet->setCellValue("B{$row}", $emp?->full_name ?? '-');
                $sheet->setCellValue("C{$row}", $rec->employee?->position?->jobGrade?->name ?? '-');
                $sheet->setCellValue("D{$row}", $emp?->department?->name ?? '-');
                $sheet->setCellValue("E{$row}", $tgl->format('d/m/Y'));
                $sheet->setCellValue("F{$row}", $jamMasuk);
                $sheet->setCellValue("G{$row}", $jamPulang);
                $sheet->setCellValue("H{$row}", 'Masuk / Pulang');
                $sheet->setCellValue("I{$row}", $masuk?->pusatLokasi?->nama_lokasi ?? $pulang?->pusatLokasi?->nama_lokasi ?? '-');
                $sheet->setCellValue("J{$row}", $this->labelStatus($masuk?->status));
                $sheet->setCellValue("K{$row}", $masuk?->menit_terlambat ?? 0);
                $sheet->setCellValue("L{$row}", $masuk?->menit_lembur ?? 0);
                $sheet->setCellValue("M{$row}", $masuk?->jarak_meter ?? '-');

                $this->applyRowStatusStyle($sheet, $row, $masuk?->status, 'A', 'M');

                $row++;
            }
        }

        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            $this->applyOuterBorder($sheet, "A{$headerRow}:M{$lastDataRow}");
        }

        $this->addSummaryTab($spreadsheet, $records, $request);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // =========================================================================
    // PRIVATE — Tab "Ringkasan" (sheet ke-2)
    // =========================================================================

    private function addSummaryTab(Spreadsheet $spreadsheet, $records, Request $request): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan');

        // Hitung statistik dari koleksi
        $total = $records->count();
        $tepatWaktu = $records->where('status', 'tepat_waktu')->count();
        $terlambat = $records->where('status', 'terlambat')->count();
        $diluarLokasi = $records->where('status', 'diluar_lokasi')->count();
        $lembur = $records->where('status', 'lembur')->count();
        $totalKaryawan = $records->pluck('employee_id')->unique()->count();
        $rataTerlambat = $records->where('menit_terlambat', '>', 0)->avg('menit_terlambat');
        $rataLembur = $records->where('menit_lembur', '>', 0)->avg('menit_lembur');

        // Judul
        $sheet->setCellValue('A1', 'RINGKASAN LAPORAN ABSENSI');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1a3a6b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A2', 'Periode: '.$request->input('tanggal_mulai').' s/d '.$request->input('tanggal_selesai'));
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(10);

        // Statistik
        $stats = [
            ['Total Record Absensi',     $total,                      '3B82F6'],
            ['Total Karyawan Aktif',      $totalKaryawan,             '8B5CF6'],
            ['Tepat Waktu',               $tepatWaktu,                '22C55E'],
            ['Terlambat',                 $terlambat,                 'EF4444'],
            ['Di Luar Lokasi',            $diluarLokasi,              'F97316'],
            ['Lembur',                    $lembur,                    '6366F1'],
            ['Rata-rata Menit Terlambat', round($rataTerlambat ?? 0, 1).' menit', 'F59E0B'],
            ['Rata-rata Menit Lembur',    round($rataLembur ?? 0, 1).' menit',    '14B8A6'],
        ];

        $r = 4;
        foreach ($stats as [$label, $value, $color]) {
            $sheet->setCellValue("A{$r}", $label);
            $sheet->setCellValue("B{$r}", $value);

            $sheet->getStyle("A{$r}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F0FE']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("B{$r}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => $color]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $border = ['style' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']];
            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                'borders' => [
                    'allBorders' => $border,
                ],
            ]);

            $sheet->getRowDimension($r)->setRowHeight(28);
            $r++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(18);
    }

    // =========================================================================
    // PRIVATE — HELPERS STYLE
    // =========================================================================

    /**
     * Tulis header metadata (judul, periode, dicetak) di baris 1–5.
     */
    private function writeMetaHeader(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        Request $request,
        string $title,
        int $lastCol
    ): void {
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol);

        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', 'PT. BPR BANK SURYA YUDHA');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a3a6b']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', $title);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1a3a6b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $periode = 'Periode: '.$request->input('tanggal_mulai').' s/d '.$request->input('tanggal_selesai');
        $sheet->mergeCells("A3:{$lastColLetter}3");
        $sheet->setCellValue('A3', $periode);
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells("A4:{$lastColLetter}4");
        $sheet->setCellValue('A4', 'Dicetak pada: '.Carbon::now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '777777']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Spasi
        $sheet->getRowDimension(5)->setRowHeight(8);
    }

    private function applyHeaderStyle(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1a3a6b'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'AAAAAA'],
                ],
            ],
        ]);
    }

    /**
     * Warna baris berdasarkan status absensi.
     */
    private function applyRowStatusStyle(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        ?string $status,
        string $colStart = 'A',
        string $colEnd = 'R'
    ): void {
        $colorMap = [
            'tepat_waktu' => 'F0FFF4', // hijau muda
            'terlambat' => 'FFF5F5', // merah muda
            'diluar_lokasi' => 'FFF8F0', // oranye muda
            'lembur' => 'F0F0FF', // ungu muda
        ];

        $bgColor = $colorMap[$status] ?? 'FFFFFF';
        $range = "{$colStart}{$row}:{$colEnd}{$row}";

        $sheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgColor],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function applyOuterBorder(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '1a3a6b'],
                ],
            ],
        ]);
    }

    // =========================================================================
    // PRIVATE — HELPERS DATA
    // =========================================================================

    private function namaHari(int $dayOfWeek): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$dayOfWeek] ?? '-';
    }

    private function labelStatus(?string $status): string
    {
        return match ($status) {
            'tepat_waktu' => 'Tepat Waktu',
            'terlambat' => 'Terlambat',
            'diluar_lokasi' => 'Di Luar Lokasi',
            'lembur' => 'Lembur',
            default => '-',
        };
    }
}
