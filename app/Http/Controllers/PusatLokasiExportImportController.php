<?php

namespace App\Http\Controllers;

use App\Models\PusatLokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class PusatLokasiExportImportController extends Controller
{
    // =========================================================================
    // EXPORT
    // GET /admin/pusat-lokasi/export
    // =========================================================================

    public function export(Request $request)
    {
        try {
            $user = $request->user();
            // FIX: ambil company_id dari user langsung (fallback dari employee)
            $companyId = $user->company_id
                ?? $user->employee?->company_id;

            if (! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data company tidak ditemukan untuk akun ini',
                ], 422);
            }

            $data = PusatLokasi::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Buat spreadsheet
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Pusat Lokasi');

            // Header
            $headers = ['ID', 'Nama Lokasi', 'Titik Kordinat', 'Keterangan', 'Status', 'Dibuat'];
            foreach ($headers as $col => $header) {
                $cell = chr(65 + $col).'1';
                $sheet->setCellValue($cell, $header);
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2563EB'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                $sheet->getColumnDimension(chr(65 + $col))->setAutoSize(true);
            }

            // Data rows
            foreach ($data as $i => $row) {
                $r = $i + 2;
                $sheet->setCellValue("A{$r}", $row->id);
                $sheet->setCellValue("B{$r}", $row->nama_lokasi);
                $sheet->setCellValue("C{$r}", $row->titik_kordinat);
                $sheet->setCellValue("D{$r}", $row->keterangan ?? '');
                $sheet->setCellValue("E{$r}", $row->is_active ? 'Aktif' : 'Nonaktif');
                $sheet->setCellValue("F{$r}", $row->created_at->format('d/m/Y H:i'));

                // Warna baris selang-seling
                if ($i % 2 === 0) {
                    $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0F7FF'],
                        ],
                    ]);
                }
            }

            $fileName = 'pusat_lokasi_'.now()->format('Ymd_His').'.xlsx';
            $tempPath = tempnam(sys_get_temp_dir(), 'pusat_lokasi_');

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($tempPath);

            Log::info('Export pusat lokasi', [
                'company_id' => $companyId,
                'count' => $data->count(),
                'by' => $user->id,
            ]);

            return response()->download($tempPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Export pusat lokasi error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DOWNLOAD TEMPLATE
    // GET /admin/pusat-lokasi/import-template
    // =========================================================================

    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Import');

            // Header — hanya 3 kolom yang dibutuhkan saat import
            $headers = ['nama_lokasi', 'titik_kordinat', 'keterangan'];
            foreach ($headers as $col => $header) {
                $cell = chr(65 + $col).'1';
                $sheet->setCellValue($cell, $header);
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2563EB'],
                    ],
                ]);
                $sheet->getColumnDimension(chr(65 + $col))->setWidth(30);
            }

            // Contoh data
            $contoh = [
                ['Kantor Pusat',   '-6.208763,106.845599', 'Gedung utama lantai 5'],
                ['Gudang Selatan', '-6.300000,106.900000', ''],
            ];

            foreach ($contoh as $i => $baris) {
                $r = $i + 2;
                $sheet->setCellValue("A{$r}", $baris[0]);
                $sheet->setCellValue("B{$r}", $baris[1]);
                $sheet->setCellValue("C{$r}", $baris[2]);

                // Warna abu muda untuk baris contoh
                $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
            }

            $fileName = 'template_import_pusat_lokasi.xlsx';
            $tempPath = tempnam(sys_get_temp_dir(), 'template_pusat_lokasi_');

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Download template error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template',
            ], 500);
        }
    }

    // =========================================================================
    // IMPORT
    // POST /admin/pusat-lokasi/import
    // Body: file (xlsx/xls/csv)
    // =========================================================================

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ]);

            $user = $request->user();
            // FIX: ambil company_id dari user langsung (fallback dari employee)
            $companyId = $user->company_id
                ?? $user->employee?->company_id;

            if (! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data company tidak ditemukan untuk akun ini',
                ], 422);
            }

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            // Pilih reader sesuai ekstensi
            if ($extension === 'csv') {
                $reader = new CsvReader;
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
            } else {
                $reader = new XlsxReader;
            }

            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong',
                ], 422);
            }

            // Baris pertama = header, normalisasi jadi lowercase tanpa spasi
            $headerRow = array_map(
                fn ($h) => strtolower(trim(str_replace(' ', '_', $h ?? ''))),
                $rows[0]
            );

            // Pastikan kolom wajib ada
            $required = ['nama_lokasi', 'titik_kordinat'];
            foreach ($required as $col) {
                if (! in_array($col, $headerRow)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Kolom '{$col}' tidak ditemukan di file. Gunakan template yang disediakan.",
                    ], 422);
                }
            }

            $namaIdx = array_search('nama_lokasi', $headerRow);
            $kordinatIdx = array_search('titik_kordinat', $headerRow);
            $keteranganIdx = array_search('keterangan', $headerRow);

            $imported = 0;
            $skipped = 0;
            $errors = [];

            // Proses baris data (mulai dari index 1, lewati header)
            foreach (array_slice($rows, 1) as $lineNum => $row) {
                $rowNum = $lineNum + 2; // nomor baris di Excel (1-based + header)

                $namaLokasi = trim($row[$namaIdx] ?? '');
                $titikKordinat = trim($row[$kordinatIdx] ?? '');

                // Skip baris kosong
                if ($namaLokasi === '' && $titikKordinat === '') {
                    continue;
                }

                // Validasi per baris
                if ($namaLokasi === '') {
                    $errors[] = "Baris {$rowNum}: nama_lokasi kosong";
                    $skipped++;

                    continue;
                }

                if ($titikKordinat === '') {
                    $errors[] = "Baris {$rowNum}: titik_kordinat kosong";
                    $skipped++;

                    continue;
                }

                // Validasi format koordinat
                $parts = explode(',', $titikKordinat);
                if (count($parts) !== 2 || ! is_numeric(trim($parts[0])) || ! is_numeric(trim($parts[1]))) {
                    $errors[] = "Baris {$rowNum}: format titik_kordinat tidak valid (contoh: -6.208763,106.845599)";
                    $skipped++;

                    continue;
                }

                $keterangan = null;
                if ($keteranganIdx !== false) {
                    $val = trim($row[$keteranganIdx] ?? '');
                    $keterangan = $val !== '' ? $val : null;
                }

                PusatLokasi::create([
                    'company_id' => $companyId,
                    'nama_lokasi' => $namaLokasi,
                    'titik_kordinat' => $titikKordinat,
                    'keterangan' => $keterangan,
                    'is_active' => true,
                ]);

                $imported++;
            }

            Log::info('Import pusat lokasi selesai', [
                'company_id' => $companyId,
                'imported' => $imported,
                'skipped' => $skipped,
                'by' => $user->id,
            ]);

            $message = "{$imported} data berhasil diimport";
            if ($skipped > 0) {
                $message .= ", {$skipped} baris dilewati";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Import pusat lokasi error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: '.$e->getMessage(),
            ], 500);
        }
    }
}
