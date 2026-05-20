<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AbsensiExportController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // EXPORT EXCEL
    // GET /admin/absensi/export
    // ──────────────────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // ── Query ─────────────────────────────────────────────────────────
            $query = Absensi::with([
                'employee:id,full_name,nickname,employee_code,nik',
                'pusatLokasi:id,nama_lokasi',
                'shift:id,nama,kode',
            ]);

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('tanggal_mulai')) {
                $query->whereDate('tanggal_absen', '>=', $request->tanggal_mulai);
            }
            if ($request->filled('tanggal_selesai')) {
                $query->whereDate('tanggal_absen', '<=', $request->tanggal_selesai);
            }
            if ($request->filled('bulan') && $request->filled('tahun')) {
                $query->whereMonth('tanggal_absen', $request->bulan)
                    ->whereYear('tanggal_absen', $request->tahun);
            } elseif ($request->filled('tahun')) {
                $query->whereYear('tanggal_absen', $request->tahun);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('tipe_absen')) {
                $query->where('tipe_absen', $request->tipe_absen);
            }

            $absensis = $query->orderBy('tanggal_absen')->orderBy('waktu_absen')->get();

            Log::info('Export Excel absensi', [
                'admin_id' => $request->user()->id,
                'total' => $absensis->count(),
            ]);

            // ── Nama file ─────────────────────────────────────────────────────
            if ($request->filled('bulan') && $request->filled('tahun')) {
                $suffix = '_'.str_pad($request->bulan, 2, '0', STR_PAD_LEFT).'-'.$request->tahun;
            } elseif ($request->filled('tanggal_mulai')) {
                $suffix = '_'.$request->tanggal_mulai.'_sd_'.($request->tanggal_selesai ?? now()->toDateString());
            } else {
                $suffix = '_'.now()->format('Y-m-d');
            }
            $fileName = 'laporan_absensi'.$suffix.'.xlsx';

            // ── Build spreadsheet ─────────────────────────────────────────────
            $spreadsheet = $this->buildSpreadsheet($absensis, $request);

            // ── Stream response ───────────────────────────────────────────────
            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                'Cache-Control' => 'max-age=0',
                'X-Total-Records' => $absensis->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error export Excel absensi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal export: '.$e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BUILD SPREADSHEET
    // ──────────────────────────────────────────────────────────────────────────

    private function buildSpreadsheet($absensis, Request $request): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        // ── Sheet 1: Data Absensi ─────────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Absensi');

        // Page setup
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);

        // ── Judul laporan ─────────────────────────────────────────────────────
        $judul = 'LAPORAN ABSENSI KARYAWAN';
        if ($request->filled('bulan') && $request->filled('tahun')) {
            $namaBulan = $this->namaBulan((int) $request->bulan);
            $judul .= ' – '.$namaBulan.' '.$request->tahun;
        } elseif ($request->filled('tanggal_mulai')) {
            $judul .= ' – '.$request->tanggal_mulai.' s/d '.($request->tanggal_selesai ?? now()->toDateString());
        }

        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', $judul);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // Sub-judul: tanggal generate
        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', 'Dicetak: '.now()->format('d/m/Y H:i').' | Total Data: '.$absensis->count().' record');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555'], 'name' => 'Arial'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF3FF']],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // Baris kosong
        $sheet->getRowDimension(3)->setRowHeight(6);

        // ── Header tabel ──────────────────────────────────────────────────────
        $headers = [
            'A' => ['No',               6],
            'B' => ['Tanggal',         12],
            'C' => ['Nama Karyawan',   24],
            'D' => ['Nickname',        14],
            'E' => ['NIK',             16],
            'F' => ['Kode Karyawan',   14],
            'G' => ['Tipe Absen',      11],
            'H' => ['Waktu Absen',     17],
            'I' => ['Lokasi',          22],
            'J' => ['Shift',           14],
            'K' => ['Status',          14],
            'L' => ['Terlambat (mnt)', 15],
            'M' => ['Lembur (mnt)',    13],
            'N' => ['Jarak (m)',       11],
            'O' => ['Wajah Cocok',     12],
            'P' => ['Catatan',         30],
        ];

        foreach ($headers as $col => [$label, $width]) {
            $sheet->getColumnDimension($col)->setWidth($width);
            $sheet->setCellValue("{$col}4", $label);
        }

        $headerRange = 'A4:P4';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '90BAF9']],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Data rows ─────────────────────────────────────────────────────────
        $rowNum = 5;
        foreach ($absensis as $i => $ab) {
            $isEven = ($i % 2 === 0);
            $bgColor = $isEven ? 'FFFFFF' : 'F0F6FF';

            $status = $ab->status ?? '-';
            $statusColor = match ($status) {
                'tepat_waktu' => '166534', // hijau tua
                'terlambat' => 'B45309', // oranye tua
                'diluar_lokasi' => 'B91C1C', // merah tua
                'lembur' => '6D28D9', // ungu
                default => '374151',
            };
            $statusBg = match ($status) {
                'tepat_waktu' => 'DCFCE7',
                'terlambat' => 'FEF3C7',
                'diluar_lokasi' => 'FEE2E2',
                'lembur' => 'EDE9FE',
                default => $bgColor,
            };

            $tipe = $ab->tipe_absen ?? '-';
            $tipeBg = $tipe === 'masuk' ? 'D1FAE5' : ($tipe === 'pulang' ? 'E0F2FE' : $bgColor);
            $tipeColor = $tipe === 'masuk' ? '065F46' : ($tipe === 'pulang' ? '075985' : '374151');

            $rowData = [
                'A' => $i + 1,
                'B' => $ab->tanggal_absen?->format('d/m/Y') ?? '',
                'C' => $ab->employee?->full_name ?? '',
                'D' => $ab->employee?->nickname ?? '',
                'E' => $ab->employee?->nik ?? '',
                'F' => $ab->employee?->employee_code ?? '',
                'G' => $tipe,
                'H' => $ab->waktu_absen?->format('d/m/Y H:i') ?? '',
                'I' => $ab->pusatLokasi?->nama_lokasi ?? '-',
                'J' => $ab->shift?->nama ?? '-',
                'K' => $status,
                'L' => $ab->menit_terlambat ?? 0,
                'M' => $ab->menit_lembur ?? 0,
                'N' => $ab->jarak_meter ?? 0,
                'O' => $ab->wajah_cocok ? 'Ya' : 'Tidak',
                'P' => $ab->catatan ?? '',
            ];

            foreach ($rowData as $col => $value) {
                $sheet->setCellValue("{$col}{$rowNum}", $value);
            }

            // Style per baris
            $sheet->getStyle("A{$rowNum}:P{$rowNum}")->applyFromArray([
                'font' => ['size' => 10, 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Center: No, Tanggal, Tipe, Waktu, Status, Terlambat, Lembur, Jarak, Wajah
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("K{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Warna status
            $sheet->getStyle("K{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $statusColor]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusBg]],
            ]);

            // Warna tipe absen
            $sheet->getStyle("G{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $tipeColor]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $tipeBg]],
            ]);

            // Merah jika terlambat > 0
            if (($ab->menit_terlambat ?? 0) > 0) {
                $sheet->getStyle("L{$rowNum}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'B45309']],
                ]);
            }

            $sheet->getRowDimension($rowNum)->setRowHeight(18);
            $rowNum++;
        }

        // ── Baris total / ringkasan ───────────────────────────────────────────
        $totalRow = $rowNum + 1;
        $lastData = $rowNum - 1;

        $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'RINGKASAN');
        $sheet->getStyle("A{$totalRow}:P{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($totalRow)->setRowHeight(20);

        $summaryRow = $totalRow + 1;
        $summaryData = [
            ['Total Record',    $absensis->count()],
            ['Total Masuk',     $absensis->where('tipe_absen', 'masuk')->count()],
            ['Total Pulang',    $absensis->where('tipe_absen', 'pulang')->count()],
            ['Tepat Waktu',     $absensis->where('status', 'tepat_waktu')->count()],
            ['Terlambat',       $absensis->where('status', 'terlambat')->count()],
            ['Di Luar Lokasi',  $absensis->where('status', 'diluar_lokasi')->count()],
            ['Lembur',          $absensis->where('menit_lembur', '>', 0)->count()],
            ['Wajah Cocok',     $absensis->where('wajah_cocok', true)->count()],
        ];

        foreach ($summaryData as $j => [$label, $value]) {
            $col = chr(65 + ($j * 2));     // A, C, E, G, ...
            $colVal = chr(65 + ($j * 2 + 1)); // B, D, F, H, ...
            $sheet->setCellValue("{$col}{$summaryRow}", $label);
            $sheet->setCellValue("{$colVal}{$summaryRow}", $value);
            $sheet->getStyle("{$col}{$summaryRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial', 'color' => ['rgb' => '1E3A5F']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF3FF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFD7FF']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $sheet->getStyle("{$colVal}{$summaryRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial', 'color' => ['rgb' => '1565C0']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFD7FF']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        $sheet->getRowDimension($summaryRow)->setRowHeight(22);

        // Freeze header
        $sheet->freezePane('A5');

        // ── Sheet 2: Rekap Per Karyawan ───────────────────────────────────────
        $sheetRekap = $spreadsheet->createSheet();
        $sheetRekap->setTitle('Rekap Per Karyawan');
        $this->buildRekapSheet($sheetRekap, $absensis);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SHEET 2: REKAP PER KARYAWAN
    // ──────────────────────────────────────────────────────────────────────────

    private function buildRekapSheet($sheet, $absensis): void
    {
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'REKAP ABSENSI PER KARYAWAN');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $rekapHeaders = ['A' => 'Nama Karyawan', 'B' => 'NIK', 'C' => 'Kode', 'D' => 'Jml Masuk', 'E' => 'Jml Pulang', 'F' => 'Terlambat', 'G' => 'Total Mnt Terlambat', 'H' => 'Lembur (Hari)'];
        $rekapWidths = ['A' => 28, 'B' => 18, 'C' => 14, 'D' => 11, 'E' => 11, 'F' => 11, 'G' => 18, 'H' => 13];

        foreach ($rekapHeaders as $col => $label) {
            $sheet->getColumnDimension($col)->setWidth($rekapWidths[$col]);
            $sheet->setCellValue("{$col}2", $label);
        }
        $sheet->getStyle('A2:H2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '90BAF9']]],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Group by employee
        $grouped = $absensis->groupBy('employee_id');
        $rowNum = 3;

        foreach ($grouped as $employeeId => $records) {
            $emp = $records->first()->employee;
            $masuk = $records->where('tipe_absen', 'masuk')->count();
            $pulang = $records->where('tipe_absen', 'pulang')->count();
            $terlambatCount = $records->where('status', 'terlambat')->count();
            $totalMenitTerlambat = $records->sum('menit_terlambat');
            $lemburHari = $records->where('menit_lembur', '>', 0)->count();

            $isEven = ($rowNum % 2 === 0);
            $bgColor = $isEven ? 'F0F6FF' : 'FFFFFF';

            $sheet->setCellValue("A{$rowNum}", $emp?->full_name ?? 'Unknown');
            $sheet->setCellValue("B{$rowNum}", $emp?->nik ?? '');
            $sheet->setCellValue("C{$rowNum}", $emp?->employee_code ?? '');
            $sheet->setCellValue("D{$rowNum}", $masuk);
            $sheet->setCellValue("E{$rowNum}", $pulang);
            $sheet->setCellValue("F{$rowNum}", $terlambatCount);
            $sheet->setCellValue("G{$rowNum}", $totalMenitTerlambat);
            $sheet->setCellValue("H{$rowNum}", $lemburHari);

            $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray([
                'font' => ['size' => 10, 'name' => 'Arial'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("D{$rowNum}:H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($terlambatCount > 0) {
                $sheet->getStyle("F{$rowNum}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'B45309']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                ]);
            }
            $sheet->getRowDimension($rowNum)->setRowHeight(18);
            $rowNum++;
        }

        $sheet->freezePane('A3');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PREVIEW (summary sebelum export)
    // GET /admin/absensi/export-preview
    // ──────────────────────────────────────────────────────────────────────────

    public function preview(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $query = Absensi::query();

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }
            if ($request->filled('tanggal_mulai')) {
                $query->whereDate('tanggal_absen', '>=', $request->tanggal_mulai);
            }
            if ($request->filled('tanggal_selesai')) {
                $query->whereDate('tanggal_absen', '<=', $request->tanggal_selesai);
            }
            if ($request->filled('bulan') && $request->filled('tahun')) {
                $query->whereMonth('tanggal_absen', $request->bulan)->whereYear('tanggal_absen', $request->tahun);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('tipe_absen')) {
                $query->where('tipe_absen', $request->tipe_absen);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => (clone $query)->count(),
                    'total_masuk' => (clone $query)->where('tipe_absen', 'masuk')->count(),
                    'total_pulang' => (clone $query)->where('tipe_absen', 'pulang')->count(),
                    'total_terlambat' => (clone $query)->where('status', 'terlambat')->count(),
                    'total_lembur' => (clone $query)->where('menit_lembur', '>', 0)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function namaBulan(int $bulan): string
    {
        return ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$bulan] ?? '';
    }
}
