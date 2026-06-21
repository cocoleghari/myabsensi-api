<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Position;
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

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = Position::with(['company'])->withCount('employees');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
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

        $positions = $query->orderBy('order')->orderBy('name')->paginate(15)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.posisi', compact('positions', 'companies'));
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.posisi-form', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'company_id' => 'required|exists:companies,id',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Position::create($data);

        return redirect()->route('admin.posisi.index')->with('success', 'Posisi berhasil ditambahkan.');
    }

    public function edit(Position $posisi)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.posisi-form', ['position' => $posisi, 'companies' => $companies]);
    }

    public function update(Request $request, Position $posisi)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'company_id' => 'required|exists:companies,id',
            'description' => 'nullable|string|max:1000',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $posisi->update($data);

        return redirect()->route('admin.posisi.index')->with('success', 'Posisi berhasil diperbarui.');
    }

    public function destroy(Position $posisi)
    {
        if ($posisi->employees()->exists()) {
            return back()->with('error', 'Posisi tidak dapat dihapus karena masih ada karyawan terdaftar.');
        }

        $posisi->delete();

        return back()->with('success', 'Posisi berhasil dihapus.');
    }

    // ── Export ke Excel ──────────────────────────────────────────────────────
    public function export(Request $request): StreamedResponse
    {
        $positions = Position::with(['company'])
            ->withCount('employees')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->orderBy('order')->orderBy('name')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Positions');

        $headers = [
            'A1' => 'ID', 'B1' => 'Nama Posisi', 'C1' => 'Kode', 'D1' => 'Company',
            'E1' => 'Deskripsi', 'F1' => 'Urutan', 'G1' => 'Status', 'H1' => 'Jumlah Karyawan',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EA580C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($positions as $pos) {
            $sheet->setCellValue("A{$row}", $pos->id);
            $sheet->setCellValue("B{$row}", $pos->name);
            $sheet->setCellValue("C{$row}", $pos->code ?? '');
            $sheet->setCellValue("D{$row}", $pos->company?->name ?? '');
            $sheet->setCellValue("E{$row}", $pos->description ?? '');
            $sheet->setCellValue("F{$row}", $pos->order);
            $sheet->setCellValue("G{$row}", $pos->is_active ? 'Aktif' : 'Nonaktif');
            $sheet->setCellValue("H{$row}", $pos->employees_count);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF7ED']],
                ]);
            }
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->freezePane('A2');

        // Sheet petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk Import');
        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN IMPORT POSISI / JABATAN');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'EA580C']],
        ]);

        $guideRows = [
            ['Kolom', 'Keterangan', 'Wajib?', 'Contoh'],
            ['name', 'Nama posisi/jabatan', 'Ya', 'Software Engineer'],
            ['code', 'Kode unik posisi per company', 'Tidak', 'SE-01'],
            ['company_id', 'ID company', 'Ya', '1'],
            ['description', 'Deskripsi singkat', 'Tidak', 'Posisi engineer level junior'],
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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EA580C']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
        $guide->getColumnDimension('A')->setWidth(18);
        $guide->getColumnDimension('B')->setWidth(50);
        $guide->getColumnDimension('C')->setWidth(12);
        $guide->getColumnDimension('D')->setWidth(35);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'positions_'.now()->format('Ymd_His').'.xlsx';

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
        $sheet->setTitle('Import Positions');

        $headers = ['name', 'code', 'company_id', 'description', 'order', 'is_active'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i).'1', $h);
        }

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EA580C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        $sheet->setCellValue('A2', 'Software Engineer');
        $sheet->setCellValue('B2', 'SE-01');
        $sheet->setCellValue('C2', '1');
        $sheet->setCellValue('D2', 'Posisi engineer level junior');
        $sheet->setCellValue('E2', '1');
        $sheet->setCellValue('F2', '1');

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template_import_positions.xlsx', [
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

                [$name, $code, $companyId, $description, $order, $isActive] = array_pad($row, 6, null);

                $rowErrors = [];
                if (empty(trim((string) $name))) {
                    $rowErrors[] = 'name wajib diisi';
                }
                if (empty($companyId) || ! \App\Models\Company::find((int) $companyId)) {
                    $rowErrors[] = 'company_id tidak valid';
                }

                if (! empty($rowErrors)) {
                    $errors[] = "Baris {$rowNum}: ".implode(', ', $rowErrors);
                    $failed++;

                    continue;
                }

                if (! empty($code)) {
                    $exists = Position::where('company_id', (int) $companyId)
                        ->where('code', trim((string) $code))
                        ->exists();
                    if ($exists) {
                        $errors[] = "Baris {$rowNum}: kode '{$code}' sudah dipakai di company ini";
                        $failed++;

                        continue;
                    }
                }

                try {
                    Position::create([
                        'name' => trim((string) $name),
                        'code' => ! empty($code) ? trim((string) $code) : null,
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
            Log::error('Position import error: '.$e->getMessage());

            return back()->with('error', 'Gagal membaca file: '.$e->getMessage());
        }
    }
}
