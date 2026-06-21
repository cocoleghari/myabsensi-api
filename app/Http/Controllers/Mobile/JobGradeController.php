<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\JobGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobGradeController extends Controller
{
    public function index(Request $request)
    {
        $query = JobGrade::with('company')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id)
            )
            ->when($request->is_active !== null && $request->is_active !== '', fn ($q) => $q->where('is_active', $request->boolean('is_active'))
            )
            ->orderBy('grade', 'desc');

        return response()->json($query->paginate($request->per_page ?? 15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'grade' => 'required|integer',
            'description' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $exists = JobGrade::where('company_id', $data['company_id'])
            ->where('code', $data['code'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Kode sudah dipakai di perusahaan ini.'], 422);
        }

        return response()->json(JobGrade::create($data), 201);
    }

    public function show(JobGrade $jobGrade)
    {
        return response()->json($jobGrade->load('company'));
    }

    public function update(Request $request, JobGrade $jobGrade)
    {
        $data = $request->validate([
            'company_id' => 'exists:companies,id',
            'code' => 'string|max:50',
            'name' => 'string|max:255',
            'grade' => 'integer',
            'description' => 'nullable|string',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $companyId = $data['company_id'] ?? $jobGrade->company_id;
        $code = $data['code'] ?? $jobGrade->code;

        $exists = JobGrade::where('company_id', $companyId)
            ->where('code', $code)
            ->where('id', '!=', $jobGrade->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Kode sudah dipakai di perusahaan ini.'], 422);
        }

        $jobGrade->update($data);

        return response()->json($jobGrade->load('company'));
    }

    public function destroy(JobGrade $jobGrade)
    {
        $jobGrade->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    /** Untuk dropdown — hanya yang aktif */
    public function list(Request $request)
    {
        return response()->json(
            JobGrade::when($request->filled('company_id'),
                fn ($q) => $q->where('company_id', $request->company_id)
            )
                ->active()
                ->get(['id', 'code', 'name', 'grade'])
        );
    }

    /**
     * GET /admin/job-grades/export
     */
    public function export(Request $request): StreamedResponse
    {
        $jobGrades = JobGrade::with('company')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->orderBy('grade', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Job Grades');

        // Header
        $headers = [
            'A1' => 'ID',
            'B1' => 'Kode',
            'C1' => 'Nama',
            'D1' => 'Grade',
            'E1' => 'Company',
            'F1' => 'Deskripsi',
            'G1' => 'Urutan',
            'H1' => 'Status',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']], // amber-700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($jobGrades as $jg) {
            $sheet->setCellValue("A{$row}", $jg->id);
            $sheet->setCellValue("B{$row}", $jg->code);
            $sheet->setCellValue("C{$row}", $jg->name);
            $sheet->setCellValue("D{$row}", $jg->grade);
            $sheet->setCellValue("E{$row}", $jg->company?->name ?? '');
            $sheet->setCellValue("F{$row}", $jg->description ?? '');
            $sheet->setCellValue("G{$row}", $jg->order);
            $sheet->setCellValue("H{$row}", $jg->is_active ? 'Aktif' : 'Nonaktif');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']], // amber-50
                ]);
            }
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->freezePane('A2');

        // Sheet petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk Import');
        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN IMPORT JOB GRADE');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'B45309']],
        ]);

        $guideRows = [
            ['Kolom', 'Keterangan', 'Wajib?', 'Contoh'],
            ['code', 'Kode unik grade per company', 'Ya', 'X_c'],
            ['name', 'Nama lengkap job grade', 'Ya', 'Wakil Kepala Wilayah - X c'],
            ['grade', 'Angka urutan hierarki (semakin besar semakin tinggi)', 'Ya', '10'],
            ['company_id', 'ID company', 'Ya', '1'],
            ['description', 'Deskripsi singkat', 'Tidak', 'Grade untuk level senior'],
            ['order', 'Urutan tampil di UI (angka)', 'Tidak', '1'],
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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
        $guide->getColumnDimension('A')->setWidth(18);
        $guide->getColumnDimension('B')->setWidth(50);
        $guide->getColumnDimension('C')->setWidth(12);
        $guide->getColumnDimension('D')->setWidth(35);

        $spreadsheet->setActiveSheetIndex(0); // ← pastikan sheet data yang aktif

        $filename = 'job_grades_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * GET /admin/job-grades/import-template
     */
    public function importTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Job Grades');

        $headers = ['code', 'name', 'grade', 'company_id', 'description', 'order', 'is_active'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i).'1', $h);
        }

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Contoh baris
        $sheet->setCellValue('A2', 'X_c');
        $sheet->setCellValue('B2', 'Wakil Kepala Wilayah - X c');
        $sheet->setCellValue('C2', '10');
        $sheet->setCellValue('D2', '1');
        $sheet->setCellValue('E2', 'Grade untuk level senior');
        $sheet->setCellValue('F2', '1');
        $sheet->setCellValue('G2', '1');

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template_import_job_grades.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * POST /admin/job-grades/import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $reader = new XlsxReader;
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $dataRows = array_slice($rows, 1); // skip header

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($dataRows as $i => $row) {
                $rowNum = $i + 2;

                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                [$code, $name, $grade, $companyId, $description, $order, $isActive] = array_pad($row, 7, null);

                // Validasi per baris
                $rowErrors = [];
                if (empty(trim((string) $code))) {
                    $rowErrors[] = 'code wajib diisi';
                }
                if (empty(trim((string) $name))) {
                    $rowErrors[] = 'name wajib diisi';
                }
                if (! is_numeric($grade)) {
                    $rowErrors[] = 'grade harus angka';
                }
                if (empty($companyId) || ! \App\Models\Company::find((int) $companyId)) {
                    $rowErrors[] = 'company_id tidak valid';
                }

                if (! empty($rowErrors)) {
                    $errors[] = "Baris {$rowNum}: ".implode(', ', $rowErrors);
                    $failed++;

                    continue;
                }

                // Cek duplikat code per company
                $exists = JobGrade::where('company_id', (int) $companyId)
                    ->where('code', trim((string) $code))
                    ->exists();
                if ($exists) {
                    $errors[] = "Baris {$rowNum}: kode '{$code}' sudah dipakai di company ini";
                    $failed++;

                    continue;
                }

                try {
                    JobGrade::create([
                        'code' => trim((string) $code),
                        'name' => trim((string) $name),
                        'grade' => (int) $grade,
                        'company_id' => (int) $companyId,
                        'description' => trim((string) ($description ?? '')),
                        'order' => is_numeric($order) ? (int) $order : 0,
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
            Log::error('JobGrade import error: '.$e->getMessage());

            return response()->json(['message' => 'Gagal membaca file: '.$e->getMessage()], 500);
        }
    }
}
