<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanAktivitasController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/laporan-aktivitas/summary
    // ─────────────────────────────────────────────────────────────────────────
    public function summary(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'tipe_aktivitas_id' => 'nullable|integer|exists:tipe_aktivitas,id',
        ]);

        // Load SEKALI dengan semua relasi yang dibutuhkan
        $records = $this->buildQuery($request)->get();

        $totalAktivitas = $records->count();
        $totalKaryawan = $records->pluck('employee_id')->unique()->count();

        // Per tipe
        $perTipe = [];
        foreach ($records as $rec) {
            $nama = $rec->tipeAktivitas?->nama ?? 'Tidak Diketahui';
            $perTipe[$nama] = ($perTipe[$nama] ?? 0) + 1;
        }

        // Rata-rata durasi
        $rataRataDurasi = 0;
        if ($totalAktivitas > 0) {
            $totalMenit = $records->sum(function ($r) {
                return Carbon::parse($r->mulai)->diffInMinutes(Carbon::parse($r->berakhir));
            });
            $rataRataDurasi = round($totalMenit / $totalAktivitas, 1);
        }

        // Top 5 karyawan
        $perKaryawan = $records
            ->groupBy('employee_id')
            ->map(fn ($group) => [
                'nama' => $group->first()->employee?->full_name ?? '-',
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->toArray();

        return response()->json([
            'total_aktivitas' => $totalAktivitas,
            'total_karyawan' => $totalKaryawan,
            'per_tipe' => empty($perTipe) ? (object) [] : (object) $perTipe,
            'per_karyawan' => array_values($perKaryawan instanceof \Illuminate\Support\Collection ? $perKaryawan->toArray() : ($perKaryawan ?: [])),
            'rata_durasi_menit' => $rataRataDurasi,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/laporan-aktivitas/export
    // Query params: tanggal_mulai, tanggal_selesai, employee_id,
    //               department_id, tipe_aktivitas_id,
    //               format (detail|rekap_karyawan|rekap_tipe)
    // ─────────────────────────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'tipe_aktivitas_id' => 'nullable|integer|exists:tipe_aktivitas,id',
            'format' => 'nullable|in:detail,rekap_karyawan,rekap_tipe',
        ]);

        $format = $request->input('format', 'detail');

        $spreadsheet = match ($format) {
            'rekap_karyawan' => $this->buildRekapKaryawanSheet($request),
            'rekap_tipe' => $this->buildRekapTipeSheet($request),
            default => $this->buildDetailSheet($request),
        };

        $mulai = $request->input('tanggal_mulai');
        $selesai = $request->input('tanggal_selesai');
        $filename = "laporan_aktivitas_{$mulai}_sd_{$selesai}.xlsx";

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
        $query = Aktivitas::query()
            ->with([
                'employee:id,employee_code,full_name,nik,department_id,position_id',
                'employee.department:id,name',
                'employee.position:id,name',
                'employee.jobGrade:id,name,code',
                'tipeAktivitas:id,nama',
                'fotos:aktivitas_id,foto_path,urutan',
            ])
            ->whereBetween('mulai', [
                $request->input('tanggal_mulai').' 00:00:00',
                $request->input('tanggal_selesai').' 23:59:59',
            ])
            ->orderBy('mulai');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // Jadi (support array)
        if ($request->filled('department_ids')) {
            $ids = (array) $request->input('department_ids');
            $query->whereHas('employee', fn ($q) => $q->whereIn('department_id', $ids)
            );
        } elseif ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->input('department_id'))
            );
        }

        if ($request->filled('tipe_aktivitas_id')) {
            $query->where('tipe_aktivitas_id', $request->input('tipe_aktivitas_id'));
        }

        return $query;
    }

    // =========================================================================
    // PRIVATE — SHEET DETAIL
    // =========================================================================

    private function buildDetailSheet(Request $request): Spreadsheet
    {
        $records = $this->buildQuery($request)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Aktivitas');

        $this->writeMetaHeader($sheet, $request, 'LAPORAN AKTIVITAS DETAIL', 12);

        $headerRow = 7;
        $headers = [
            'A' => ['No',              5],
            'B' => ['Nama Karyawan',  26],
            'C' => ['Jabatan',        20],
            'D' => ['Department',     20],
            'E' => ['Tipe Aktivitas', 18],
            'F' => ['Tugas',          30],
            'G' => ['Tujuan',         22],
            'H' => ['Mulai',          18],
            'I' => ['Berakhir',       18],
            'J' => ['Durasi',         12],
            'K' => ['Kendaraan',      14],
            'L' => ['Koordinat',      24],
            'M' => ['Akurasi (m)',    12],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue($col.$headerRow, $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $this->applyHeaderStyle($sheet, "A{$headerRow}:M{$headerRow}");

        $row = $headerRow + 1;
        $no = 1;

        foreach ($records as $rec) {
            $mulai = Carbon::parse($rec->mulai);
            $berakhir = Carbon::parse($rec->berakhir);
            $durasi = $this->formatDurasi($mulai->diffInMinutes($berakhir));
            $koordinat = ($rec->latitude && $rec->longitude)
                ? "{$rec->latitude}, {$rec->longitude}"
                : '-';

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $rec->employee?->full_name ?? '-');
            $sheet->setCellValue("C{$row}", $rec->employee?->position?->name ?? '-'); // ← tambah
            $sheet->setCellValue("D{$row}", $rec->employee?->department?->name ?? '-');
            $sheet->setCellValue("E{$row}", $rec->tipeAktivitas?->nama ?? '-');
            $sheet->setCellValue("F{$row}", $rec->tugas ?? '-');
            $sheet->setCellValue("G{$row}", $rec->tujuan ?? '-');
            $sheet->setCellValue("H{$row}", $mulai->format('d/m/Y H:i'));
            $sheet->setCellValue("I{$row}", $berakhir->format('d/m/Y H:i'));
            $sheet->setCellValue("J{$row}", $durasi);
            $sheet->setCellValue("K{$row}", $rec->kendaraan_nopol ?? '-');
            $sheet->setCellValue("L{$row}", $koordinat);
            $sheet->setCellValue("M{$row}", $rec->akurasi_meter ?? '-');

            $this->applyDataRowStyle($sheet, $row, 'A', 'M', $no % 2 === 0);
            $row++;
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
    // PRIVATE — SHEET REKAP PER KARYAWAN
    // =========================================================================

    private function buildRekapKaryawanSheet(Request $request): Spreadsheet
    {
        $records = $this->buildQuery($request)->get();

        // Kelompokkan per karyawan
        $grouped = [];
        foreach ($records as $rec) {
            $empId = $rec->employee_id;
            if (! isset($grouped[$empId])) {
                $grouped[$empId] = [
                    'employee' => $rec->employee,
                    'records' => [],
                ];
            }
            $grouped[$empId]['records'][] = $rec;
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Per Karyawan');

        $this->writeMetaHeader($sheet, $request, 'REKAP AKTIVITAS PER KARYAWAN', 8);

        $headerRow = 7;
        $headers = [
            'A' => ['No',                5],
            'B' => ['Nama Karyawan',    26],
            'C' => ['Jabatan',          20],
            'D' => ['Department',       20],
            'E' => ['Total Aktivitas',  16],
            'F' => ['Total Durasi',     14],
            'G' => ['Tipe Terbanyak',   20],
            'H' => ['Aktivitas Pertama', 20],
            'I' => ['Aktivitas Terakhir', 20],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue($col.$headerRow, $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $this->applyHeaderStyle($sheet, "A{$headerRow}:I{$headerRow}");

        $row = $headerRow + 1;
        $no = 1;

        foreach ($grouped as $empData) {
            $emp = $empData['employee'];
            $recs = collect($empData['records']);
            $total = $recs->count();

            $totalMenit = $recs->sum(function ($r) {
                return Carbon::parse($r->mulai)->diffInMinutes(Carbon::parse($r->berakhir));
            });

            $tipeTerbanyak = $recs
                ->groupBy(fn ($r) => $r->tipeAktivitas?->nama ?? '-')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first() ?? '-';

            $pertama = Carbon::parse($recs->min('mulai'))->format('d/m/Y H:i');
            $terakhir = Carbon::parse($recs->max('berakhir'))->format('d/m/Y H:i');

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $emp?->full_name ?? '-');
            $sheet->setCellValue("C{$row}", $emp?->position?->name ?? '-');
            $sheet->setCellValue("D{$row}", $emp?->department?->name ?? '-');
            $sheet->setCellValue("E{$row}", $total);
            $sheet->setCellValue("F{$row}", $this->formatDurasi($totalMenit));
            $sheet->setCellValue("G{$row}", $tipeTerbanyak);
            $sheet->setCellValue("H{$row}", $pertama);
            $sheet->setCellValue("I{$row}", $terakhir);

            $this->applyDataRowStyle($sheet, $row, 'A', 'I', $no % 2 === 0);
            $row++;
        }

        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            $this->applyOuterBorder($sheet, "A{$headerRow}:I{$lastDataRow}");
        }

        $this->addSummaryTab($spreadsheet, $records, $request);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // =========================================================================
    // PRIVATE — SHEET REKAP PER TIPE AKTIVITAS
    // =========================================================================

    private function buildRekapTipeSheet(Request $request): Spreadsheet
    {
        $records = $this->buildQuery($request)->get();

        // Kelompokkan per tipe
        $grouped = [];
        foreach ($records as $rec) {
            $tipe = $rec->tipeAktivitas?->nama ?? 'Tidak Diketahui';
            if (! isset($grouped[$tipe])) {
                $grouped[$tipe] = [];
            }
            $grouped[$tipe][] = $rec;
        }

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Per Tipe');

        $this->writeMetaHeader($sheet, $request, 'REKAP AKTIVITAS PER TIPE', 7);

        $headerRow = 7;
        $headers = [
            'A' => ['No',              5],
            'B' => ['Tipe Aktivitas', 24],
            'C' => ['Total Aktivitas', 16],
            'D' => ['Total Karyawan', 16],
            'E' => ['Total Durasi',   14],
            'F' => ['Rata Durasi',    14],
            'G' => ['% dari Total',   14],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $sheet->setCellValue($col.$headerRow, $label);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $this->applyHeaderStyle($sheet, "A{$headerRow}:G{$headerRow}");

        $row = $headerRow + 1;
        $no = 1;
        $grandTotal = $records->count();

        foreach ($grouped as $tipe => $recs) {
            $col = collect($recs);
            $total = $col->count();
            $karyawan = $col->pluck('employee_id')->unique()->count();

            $totalMenit = $col->sum(function ($r) {
                return Carbon::parse($r->mulai)->diffInMinutes(Carbon::parse($r->berakhir));
            });
            $rataMenit = $total > 0 ? round($totalMenit / $total, 1) : 0;
            $persen = $grandTotal > 0 ? round($total / $grandTotal * 100, 1) : 0;

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $tipe);
            $sheet->setCellValue("C{$row}", $total);
            $sheet->setCellValue("D{$row}", $karyawan);
            $sheet->setCellValue("E{$row}", $this->formatDurasi($totalMenit));
            $sheet->setCellValue("F{$row}", $this->formatDurasi((int) $rataMenit));
            $sheet->setCellValue("G{$row}", "{$persen}%");

            $this->applyDataRowStyle($sheet, $row, 'A', 'G', $no % 2 === 0);
            $row++;
        }

        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow + 1) {
            $this->applyOuterBorder($sheet, "A{$headerRow}:G{$lastDataRow}");
        }

        $this->addSummaryTab($spreadsheet, $records, $request);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // =========================================================================
    // PRIVATE — Tab Ringkasan
    // =========================================================================

    private function addSummaryTab(Spreadsheet $spreadsheet, $records, Request $request): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan');

        $col = collect($records);
        $total = $col->count();
        $karyawan = $col->pluck('employee_id')->unique()->count();

        $totalMenit = $col->sum(function ($r) {
            return Carbon::parse($r->mulai)->diffInMinutes(Carbon::parse($r->berakhir));
        });
        $rataMenit = $total > 0 ? round($totalMenit / $total, 1) : 0;

        $sheet->setCellValue('A1', 'RINGKASAN LAPORAN AKTIVITAS');
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

        $stats = [
            ['Total Aktivitas',      $total,                         '3B82F6'],
            ['Total Karyawan',        $karyawan,                     '8B5CF6'],
            ['Total Durasi',          $this->formatDurasi($totalMenit), '22C55E'],
            ['Rata-rata Durasi',      $this->formatDurasi((int) $rataMenit), '14B8A6'],
        ];

        // Tambah per tipe
        $perTipe = $col->groupBy(fn ($r) => $r->tipeAktivitas?->nama ?? '-');
        foreach ($perTipe as $tipe => $recs) {
            $stats[] = ["Tipe: {$tipe}", $recs->count(), 'F97316'];
        }

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

            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                'borders' => ['allBorders' => ['style' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(28);
            $r++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    // =========================================================================
    // PRIVATE — STYLE HELPERS
    // =========================================================================

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

        $sheet->mergeCells("A3:{$lastColLetter}3");
        $sheet->setCellValue('A3', 'Periode: '.$request->input('tanggal_mulai').' s/d '.$request->input('tanggal_selesai'));
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

        $sheet->getRowDimension(5)->setRowHeight(8);
    }

    private function applyHeaderStyle(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a3a6b']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']],
            ],
        ]);
    }

    private function applyDataRowStyle(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        string $colStart,
        string $colEnd,
        bool $isEven = false
    ): void {
        $bgColor = $isEven ? 'F8FAFF' : 'FFFFFF';
        $range = "{$colStart}{$row}:{$colEnd}{$row}";

        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function applyOuterBorder(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1a3a6b']],
            ],
        ]);
    }

    // =========================================================================
    // PRIVATE — DATA HELPERS
    // =========================================================================

    private function formatDurasi(int $menit): string
    {
        if ($menit <= 0) {
            return '0 mnt';
        }
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;
        if ($jam > 0) {
            return $sisa > 0 ? "{$jam}j {$sisa}mnt" : "{$jam}j";
        }

        return "{$sisa}mnt";
    }
}
