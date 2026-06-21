<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
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

class DepartmentController extends Controller
{
    /**
     * GET /admin/departments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Department::with(['company', 'parent', 'manager'])
                ->withCount('employees');

            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } elseif ($request->boolean('root_only')) {
                $query->whereNull('parent_id');
            }
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('code', 'like', '%'.$request->search.'%');
                });
            }
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $departments = $query->orderBy('order')->get();

            return response()->json(
                [
                    'data' => $departments->toArray(),
                    'total' => $departments->count(),
                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            );

        } catch (\Throwable $e) {
            \Log::error('DepartmentController@index error: '.$e->getMessage());

            return response()->json(['message' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * GET /admin/departments-tree
     */
    public function tree(Request $request): JsonResponse
    {
        $query = Department::with(['allChildren.manager', 'manager'])
            ->withCount('employees')
            ->whereNull('parent_id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json([
            'data' => $query->orderBy('order')->get(),
        ]);
    }

    /**
     * POST /admin/departments
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => [
                'nullable',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($request) {
                    if (! $value) {
                        return;
                    }
                    $employee = Employee::find($value);
                    if ($employee && (int) $employee->company_id !== (int) $request->company_id) {
                        $fail('Manager harus berasal dari company yang sama.');
                    }
                },
            ],
            'name' => 'required|string|max:100',
            'code' => [
                'nullable', 'string', 'max:20',
                Rule::unique('departments', 'code')
                    ->where('company_id', $request->company_id),
            ],
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $department = Department::create([
            'company_id' => $request->company_id,
            'parent_id' => $request->parent_id,
            'manager_id' => $request->manager_id,
            'name' => $this->cleanString($request->name),
            'code' => $this->cleanString($request->code),
            'description' => $this->cleanString($request->description),
            'order' => $request->order ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Department berhasil dibuat',
            'data' => $department->load(['company', 'parent', 'manager']),
        ], 201);
    }

    /**
     * GET /admin/departments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $department = Department::with([
            'company', 'parent', 'manager',
            'children.manager', 'employees', 'positions',
        ])->withCount('employees')->findOrFail($id);

        return response()->json(['data' => $department]);
    }

    /**
     * PUT /admin/departments/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_id' => 'sometimes|exists:companies,id',
            'parent_id' => [
                'nullable',
                'exists:departments,id',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value == $id) {
                        $fail('Department tidak bisa menjadi parent dirinya sendiri.');

                        return;
                    }
                    if ($value && $this->wouldCreateCycle($id, $value)) {
                        $fail('Perubahan ini akan membuat circular hierarchy.');
                    }
                },
            ],
            'manager_id' => [
                'nullable',
                'exists:employees,id',
                function ($attribute, $value, $fail) use ($request, $department) {
                    if (! $value) {
                        return;
                    }
                    $companyId = $request->company_id ?? $department->company_id;
                    $employee = Employee::find($value);
                    if ($employee && (int) $employee->company_id !== (int) $companyId) {
                        $fail('Manager harus berasal dari company yang sama.');
                    }
                },
            ],
            'name' => 'sometimes|string|max:100',
            'code' => [
                'nullable', 'string', 'max:20',
                Rule::unique('departments', 'code')
                    ->where('company_id', $request->company_id ?? $department->company_id)
                    ->ignore($id),
            ],
            'description' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $department->update([
            'company_id' => $request->company_id ?? $department->company_id,
            'parent_id' => array_key_exists('parent_id', $request->all())
                                 ? $request->parent_id
                                 : $department->parent_id,
            'manager_id' => array_key_exists('manager_id', $request->all())
                                 ? $request->manager_id
                                 : $department->manager_id,
            'name' => $this->cleanString($request->name) ?? $department->name,
            'code' => array_key_exists('code', $request->all())
                                 ? $this->cleanString($request->code)
                                 : $department->code,
            'description' => array_key_exists('description', $request->all())
                                 ? $this->cleanString($request->description)
                                 : $department->description,
            'order' => $request->order ?? $department->order,
            'is_active' => $request->has('is_active')
                                 ? $request->boolean('is_active')
                                 : $department->is_active,
        ]);

        return response()->json([
            'message' => 'Department berhasil diperbarui',
            'data' => $department->fresh(['company', 'parent', 'manager']),
        ]);
    }

    /**
     * DELETE /admin/departments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $department = Department::withCount(['children', 'employees'])->findOrFail($id);

        if ($department->children_count > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus department yang masih memiliki sub-department.',
            ], 422);
        }

        if ($department->employees_count > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus department yang masih memiliki karyawan.',
            ], 422);
        }

        $department->delete();

        return response()->json(['message' => 'Department berhasil dihapus']);
    }

    /**
     * POST /admin/departments/{id}/bulk-assign
     */
    public function bulkAssign(Request $request, int $id): JsonResponse
    {
        $department = Department::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'employee_ids' => 'present|array',
            'employee_ids.*' => 'integer|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newIds = collect($request->employee_ids)->unique()->values();

        // Inisialisasi di luar closure agar tidak undefined jika transaksi gagal
        $assigned = 0;
        $unassigned = 0;

        DB::transaction(function () use ($department, $newIds, &$assigned, &$unassigned) {
            $unassigned = Employee::where('department_id', $department->id)
                ->whereNotIn('id', $newIds)
                ->update(['department_id' => null]);

            $assigned = Employee::whereIn('id', $newIds)
                ->update(['department_id' => $department->id]);
        });

        return response()->json([
            'message' => "Berhasil menetapkan {$assigned} karyawan ke department {$department->name}.",
            'assigned' => $assigned,
            'unassigned' => $unassigned,
        ]);
    }

    /**
     * GET /admin/employees-list
     */
    public function employeesForAssign(Request $request): JsonResponse
    {
        $employees = Employee::with([
            'department:id,name',
            'position:id,name',
            'company:id,name',
        ])
            ->select([
                'id', 'employee_code', 'full_name', 'nickname',
                'photo_url', 'department_id', 'position_id', 'company_id',
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('full_name', 'like', '%'.$request->search.'%')
                        ->orWhere('employee_code', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->orderBy('full_name')
            ->paginate(50);

        return response()->json(
            [
                'data' => $employees->items(),
                'meta' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'total' => $employees->total(),
                ],
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    /**
     * GET /admin/departments/export
     * Download semua department sebagai file .xlsx
     */
    public function export(Request $request): StreamedResponse
    {
        // Ambil semua department dengan relasi
        $departments = Department::with(['company', 'parent', 'manager'])
            ->withCount('employees')
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->orderBy('order')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Departments');

        // ── Header styling ──────────────────────────────────────────────
        $headers = [
            'A1' => 'ID',
            'B1' => 'Nama Department',
            'C1' => 'Kode',
            'D1' => 'Company',
            'E1' => 'Parent Department',
            'F1' => 'Manager',
            'G1' => 'Deskripsi',
            'H1' => 'Urutan',
            'I1' => 'Status',
            'J1' => 'Jumlah Karyawan',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C2D91']], // deep purple
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Data rows ──────────────────────────────────────────────────
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

            // Zebra striping
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F0FA']],
                ]);
            }

            // Border tipis tiap baris
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
            ]);

            $row++;
        }

        // ── Column widths ──────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(35);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(18);

        // Freeze header row
        $sheet->freezePane('A2');

        // ── Sheet kedua: Petunjuk Import ───────────────────────────────
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk Import');

        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN IMPORT DEPARTMENT');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '5C2D91']],
        ]);

        $guideRows = [
            ['Kolom', 'Keterangan', 'Wajib?', 'Contoh'],
            ['name', 'Nama department', 'Ya', 'Engineering'],
            ['code', 'Kode unik department (per company)', 'Tidak', 'ENG-01'],
            ['company_id', 'ID company (lihat tabel company)', 'Ya', '1'],
            ['parent_id', 'ID department parent (kosongkan jika root)', 'Tidak', '3'],
            ['manager_id', 'ID employee sebagai manajer', 'Tidak', '12'],
            ['description', 'Deskripsi singkat', 'Tidak', 'Divisi rekayasa perangkat lunak'],
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
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C2D91']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        $guide->getColumnDimension('A')->setWidth(18);
        $guide->getColumnDimension('B')->setWidth(45);
        $guide->getColumnDimension('C')->setWidth(12);
        $guide->getColumnDimension('D')->setWidth(35);

        // ── Stream response ────────────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0); // ← pastikan sheet data (Departments) yang aktif
        $filename = 'departments_'.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * GET /admin/departments/import-template
     * Download template kosong untuk import
     */
    public function importTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Departments');

        $headers = ['name', 'code', 'company_id', 'parent_id', 'manager_id', 'description', 'order', 'is_active'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i); // A, B, C, ...
            $sheet->setCellValue("{$col}1", $h);
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5C2D91']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        // Contoh baris
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

    /**
     * POST /admin/departments/import
     * Import department dari file .xlsx
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'File tidak valid',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reader = new XlsxReader;
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($request->file('file')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false); // indexed dari 0

            // Baris pertama = header, lewati
            $dataRows = array_slice($rows, 1);

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($dataRows as $i => $row) {
                $rowNum = $i + 2; // nomor baris di Excel (1-indexed, +1 karena header)

                // Skip baris kosong
                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                [$name, $code, $companyId, $parentId, $managerId, $description, $order, $isActive] = array_pad($row, 8, null);

                // Validasi per baris
                $rowErrors = [];
                if (empty(trim((string) $name))) {
                    $rowErrors[] = 'name wajib diisi';
                }
                if (empty($companyId) || ! \App\Models\Company::find((int) $companyId)) {
                    $rowErrors[] = 'company_id tidak valid';
                }
                if (! empty($parentId) && ! Department::find((int) $parentId)) {
                    $rowErrors[] = 'parent_id tidak ditemukan';
                }
                if (! empty($managerId) && ! \App\Models\Employee::find((int) $managerId)) {
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

            return response()->json([
                'message' => "Import selesai: {$success} berhasil, {$failed} gagal.",
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Department import error: '.$e->getMessage());

            return response()->json(['message' => 'Gagal membaca file: '.$e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = preg_replace('/[^\P{C}\t\n\r]/u', '', $value ?? '');

        return $value;
    }

    private function wouldCreateCycle(int $departmentId, int $newParentId): bool
    {
        $current = Department::find($newParentId);
        while ($current !== null) {
            if ($current->id === $departmentId) {
                return true;
            }
            $current = $current->parent_id
                ? Department::find($current->parent_id)
                : null;
        }

        return false;
    }
}
