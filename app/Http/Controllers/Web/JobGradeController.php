<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
        $query = JobGrade::with(['company'])->withCount('employees');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
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

        $jobGrades = $query->orderBy('grade', 'desc')->paginate(15)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.job-grade', compact('jobGrades', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.job-grade-form', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'grade' => 'required|integer',
            'company_id' => 'required|exists:companies,id',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = JobGrade::where('company_id', $data['company_id'])
            ->where('code', $data['code'])->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Kode sudah dipakai di company ini.'])->withInput();
        }

        $data['is_active'] = $request->boolean('is_active', true);

        JobGrade::create($data);

        return redirect()->route('admin.job-grade.index')->with('success', 'Job grade berhasil ditambahkan.');
    }

    public function edit(JobGrade $jobGrade)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.job-grade-form', ['jobGrade' => $jobGrade, 'companies' => $companies]);
    }

    public function update(Request $request, JobGrade $jobGrade)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'grade' => 'required|integer',
            'company_id' => 'required|exists:companies,id',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = JobGrade::where('company_id', $data['company_id'])
            ->where('code', $data['code'])
            ->where('id', '!=', $jobGrade->id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Kode sudah dipakai di company ini.'])->withInput();
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $jobGrade->update($data);

        return redirect()->route('admin.job-grade.index')->with('success', 'Job grade berhasil diperbarui.');
    }

    public function destroy(JobGrade $jobGrade)
    {
        if ($jobGrade->employees()->exists()) {
            return back()->with('error', 'Job grade tidak dapat dihapus karena masih ada karyawan terdaftar.');
        }

        $jobGrade->delete();

        return back()->with('success', 'Job grade berhasil dihapus.');
    }

    // ── Export ke Excel ──────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $jobGrades = JobGrade::with(['company'])
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->orderBy('grade', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Job Grades');

        $headers = [
            'A1' => 'ID', 'B1' => 'Kode', 'C1' => 'Nama', 'D1' => 'Grade',
            'E1' => 'Company', 'F1' => 'Deskripsi', 'G1' => 'Urutan', 'H1' => 'Status',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
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

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'job_grades_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // ── Download template import ─────────────────────────────────────────────
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

    // ── Import dari Excel ────────────────────────────────────────────────────
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

                [$code, $name, $grade, $companyId, $description, $order, $isActive] = array_pad($row, 7, null);

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

            $msg = "Import selesai: {$success} berhasil, {$failed} gagal.";
            if (! empty($errors)) {
                $msg .= ' Detail error: '.implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $msg .= ' (dan '.(count($errors) - 5).' error lainnya)';
                }
            }

            return back()->with($failed > 0 && $success === 0 ? 'error' : 'success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('JobGrade import error: '.$e->getMessage());

            return back()->with('error', 'Gagal membaca file: '.$e->getMessage());
        }
    }
}
