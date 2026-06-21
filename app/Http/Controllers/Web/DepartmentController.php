<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with(['company', 'parent', 'manager'])
            ->withCount('employees');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        $departments = $query->orderBy('name')->paginate(20)->withQueryString();
        $companies = Company::select('id', 'name')->orderBy('name')->get();

        return view('admin.department', compact('departments', 'companies'));
    }

    public function tree(Request $request)
    {
        $query = Department::with(['allChildren.manager', 'manager'])
            ->withCount('employees')
            ->whereNull('parent_id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json(['data' => $query->orderBy('order')->get()]);
    }

    public function create()
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $departments = Department::select('id', 'name', 'company_id')->orderBy('name')->get();
        $employees = Employee::select('id', 'full_name', 'employee_code')->orderBy('full_name')->get();

        return view('admin.department-form', compact('department', 'companies', 'departments', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'name' => 'required|string|max:100',
            'code' => ['nullable', 'string', 'max:20', Rule::unique('departments', 'code')->where('company_id', $request->company_id)],
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if (! empty($data['manager_id'])) {
            $employee = Employee::find($data['manager_id']);
            if ($employee && (int) $employee->company_id !== (int) $data['company_id']) {
                return back()->withErrors(['manager_id' => 'Manager harus berasal dari company yang sama.'])->withInput();
            }
        }

        Department::create([
            'company_id' => $data['company_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'name' => $this->cleanString($data['name']),
            'code' => $this->cleanString($data['code'] ?? null),
            'description' => $this->cleanString($data['description'] ?? null),
            'order' => $data['order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.department.index')->with('success', 'Department berhasil dibuat.');
    }

    public function edit(Department $department)
    {
        $companies = Company::select('id', 'name')->orderBy('name')->get();
        $departments = Department::select('id', 'name', 'company_id')->where('id', '!=', $department->id)->orderBy('name')->get();
        $employees = Employee::select('id', 'full_name', 'employee_code')
            ->orderBy('full_name')
            ->get();

        return view('admin.department-form', compact('department', 'companies', 'departments', 'employees'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'name' => 'required|string|max:100',
            'code' => ['nullable', 'string', 'max:20', Rule::unique('departments', 'code')->where('company_id', $request->company_id ?? $department->company_id)->ignore($department->id)],
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if (! empty($data['parent_id'])) {
            if ($data['parent_id'] == $department->id) {
                return back()->withErrors(['parent_id' => 'Department tidak bisa menjadi parent dirinya sendiri.'])->withInput();
            }
            if ($this->wouldCreateCycle($department->id, $data['parent_id'])) {
                return back()->withErrors(['parent_id' => 'Perubahan ini akan membuat circular hierarchy.'])->withInput();
            }
        }

        if (! empty($data['manager_id'])) {
            $companyId = $data['company_id'] ?? $department->company_id;
            $employee = Employee::find($data['manager_id']);
            if ($employee && (int) $employee->company_id !== (int) $companyId) {
                return back()->withErrors(['manager_id' => 'Manager harus berasal dari company yang sama.'])->withInput();
            }
        }

        $department->update([
            'company_id' => $data['company_id'] ?? $department->company_id,
            'parent_id' => $data['parent_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'name' => $this->cleanString($data['name']),
            'code' => $this->cleanString($data['code'] ?? null),
            'description' => $this->cleanString($data['description'] ?? null),
            'order' => $data['order'] ?? $department->order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.department.index')->with('success', 'Department berhasil diperbarui.');
    }

    public function destroy(Department $department)
    {
        $department->loadCount(['children', 'employees']);

        if ($department->children_count > 0) {
            return redirect()->route('admin.department.index')
                ->with('error', 'Tidak dapat menghapus department yang masih memiliki sub-department.');
        }

        if ($department->employees_count > 0) {
            return redirect()->route('admin.department.index')
                ->with('error', 'Tidak dapat menghapus department yang masih memiliki karyawan.');
        }

        $department->delete();

        return redirect()->route('admin.department.index')->with('success', 'Department berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Export / Import
    // -------------------------------------------------------------------------

    public function export(Request $request)
    {
        $departments = Department::with(['company', 'parent', 'manager'])
            ->withCount('employees')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->orderBy('order')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Departments');

        $headers = [
            'A1' => 'ID', 'B1' => 'Nama Department', 'C1' => 'Kode', 'D1' => 'Company',
            'E1' => 'Parent Department', 'F1' => 'Manager', 'G1' => 'Deskripsi',
            'H1' => 'Urutan', 'I1' => 'Status', 'J1' => 'Jumlah Karyawan',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f2d6b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $row = 2;
        foreach ($departments as $dept) {
            $sheet->setCellValue("A{$row}", $dept->id);
            $sheet->setCellValue("B{$row}", $dept->name);
            $sheet->setCellValue("C{$row}", $dept->code ?? '');
            $sheet->setCellValue("D{$row}", $dept->company?->name ?? '');
            $sheet->setCellValue("E{$row}", $dept->parent?->name ?? '');
            $sheet->setCellValue("F{$row}", $dept->manager?->full_name ?? '');
            $sheet->setCellValue("G{$row}", $dept->description ?? '');
            $sheet->setCellValue("H{$row}", $dept->order ?? 0);
            $sheet->setCellValue("I{$row}", $dept->is_active ? 'Aktif' : 'Nonaktif');
            $sheet->setCellValue("J{$row}", $dept->employees_count);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                ]);
            }
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);
            $row++;
        }

        foreach (['A' => 8, 'B' => 30, 'C' => 15, 'D' => 25, 'E' => 25, 'F' => 25, 'G' => 35, 'H' => 10, 'I' => 12, 'J' => 18] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->freezePane('A2');

        // Sheet petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk Import');
        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN IMPORT DEPARTMENT');
        $guide->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0f2d6b']]]);

        $guideRows = [
            ['Kolom', 'Keterangan', 'Wajib?', 'Contoh'],
            ['name', 'Nama department', 'Ya', 'Engineering'],
            ['code', 'Kode unik department (per company)', 'Tidak', 'ENG-01'],
            ['company_id', 'ID company', 'Ya', '1'],
            ['parent_id', 'ID department parent (kosongkan jika root)', 'Tidak', '3'],
            ['manager_id', 'ID employee sebagai manajer', 'Tidak', '12'],
            ['description', 'Deskripsi singkat', 'Tidak', 'Divisi rekayasa'],
            ['order', 'Urutan tampil (angka)', 'Tidak', '1'],
            ['is_active', 'Status: 1=Aktif, 0=Nonaktif', 'Tidak', '1'],
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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f2d6b']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
        foreach (['A' => 18, 'B' => 45, 'C' => 12, 'D' => 35] as $col => $width) {
            $guide->getColumnDimension($col)->setWidth($width);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'departments_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Departments');

        $headers = ['name', 'code', 'company_id', 'parent_id', 'manager_id', 'description', 'order', 'is_active'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f2d6b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        $sheet->setCellValue('A2', 'Engineering');
        $sheet->setCellValue('B2', 'ENG-01');
        $sheet->setCellValue('C2', '1');
        $sheet->setCellValue('D2', '');
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', 'Divisi Engineering');
        $sheet->setCellValue('G2', '1');
        $sheet->setCellValue('H2', '1');

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template_import_departments.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $reader = new XlsxReader;
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
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

                [$name, $code, $companyId, $parentId, $managerId, $description, $order, $isActive] = array_pad($row, 8, null);

                $rowErrors = [];
                if (empty(trim((string) $name))) {
                    $rowErrors[] = 'name wajib diisi';
                }
                if (empty($companyId) || ! Company::find((int) $companyId)) {
                    $rowErrors[] = 'company_id tidak valid';
                }
                if (! empty($parentId) && ! Department::find((int) $parentId)) {
                    $rowErrors[] = 'parent_id tidak ditemukan';
                }
                if (! empty($managerId) && ! Employee::find((int) $managerId)) {
                    $rowErrors[] = 'manager_id tidak ditemukan';
                }

                if (! empty($rowErrors)) {
                    $errors[] = "Baris {$rowNum}: ".implode(', ', $rowErrors);
                    $failed++;

                    continue;
                }

                try {
                    Department::create([
                        'name' => $this->cleanString(trim((string) $name)),
                        'code' => $this->cleanString(trim((string) ($code ?? ''))),
                        'company_id' => (int) $companyId,
                        'parent_id' => ! empty($parentId) ? (int) $parentId : null,
                        'manager_id' => ! empty($managerId) ? (int) $managerId : null,
                        'description' => $this->cleanString(trim((string) ($description ?? ''))),
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

            return redirect()->route('admin.department.index')
                ->with('success', "Import selesai: {$success} berhasil, {$failed} gagal.")
                ->with('import_errors', $errors);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Department import error: '.$e->getMessage());

            return redirect()->route('admin.department.index')
                ->with('error', 'Gagal membaca file: '.$e->getMessage());
        }
    }

    public function treeHtml(Request $request)
    {
        $query = Department::with(['allChildren.manager', 'allChildren.company', 'manager'])
            ->withCount('employees')
            ->whereNull('parent_id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $departments = $query->orderBy('order')->get();

        return view('admin.partials.department-tree-content', compact('departments'))->render();
    }

    public function employeesHtml(Department $department)
    {
        $employees = $department->employees()
            ->select('id', 'full_name', 'nik', 'employee_code', 'position_id', 'photo_url')
            ->with('position:id,name')
            ->orderBy('full_name')
            ->get();

        return view('admin.partials.department-employees-content', compact('department', 'employees'))->render();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return $value;
    }

    private function wouldCreateCycle(int $departmentId, int $newParentId): bool
    {
        $current = Department::find($newParentId);
        while ($current !== null) {
            if ($current->id === $departmentId) {
                return true;
            }
            $current = $current->parent_id ? Department::find($current->parent_id) : null;
        }

        return false;
    }
}
