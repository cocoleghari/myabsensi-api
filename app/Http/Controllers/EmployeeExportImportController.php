<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeExportImportController extends Controller
{
    // ── EXPORT ────────────────────────────────────────────────────────────────

    public function export(Request $request): StreamedResponse
    {
        $query = Employee::with(['company', 'department', 'position', 'jobLevel', 'jobGrade', 'status', 'user'])
            ->withoutTrashed();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                    ->orWhere('nik', 'like', "%$search%")
                    ->orWhere('employee_code', 'like', "%$search%");
            });
        }
        if ($v = $request->query('company_id')) {
            $query->where('company_id', $v);
        }
        if ($v = $request->query('department_id')) {
            $query->where('department_id', $v);
        }
        if ($v = $request->query('job_level_id')) {
            $query->where('job_level_id', $v);
        }
        if ($v = $request->query('job_grade_id')) {
            $query->where('job_grade_id', $v);
        }
        if ($v = $request->query('employment_type')) {
            $query->where('employment_type', $v);
        }

        $employees = $query->orderBy('full_name')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Karyawan');

        $headers = [
            'ID', 'Kode Karyawan', 'NIK', 'Nomor KTP', 'Nama Lengkap', 'Nama Panggilan',
            'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Status Menikah',
            'Agama', 'Golongan Darah', 'No. HP', 'Alamat', 'Kota', 'Provinsi', 'Kode Pos',
            'Perusahaan', 'Departemen', 'Jabatan', 'Job Level', 'Job Grade',
            'Status Karyawan', 'Tipe Kepegawaian', 'Tanggal Bergabung', 'Akhir Kontrak', 'Tanggal Resign',
            'NPWP', 'BPJS Kesehatan', 'BPJS Ketenagakerjaan',
            'Nama Bank', 'No. Rekening', 'Nama Pemilik Rekening',
            'Pendidikan Terakhir', 'Jurusan', 'Institusi Pendidikan',
            'Kontak Darurat', 'No. HP Darurat', 'Hubungan Darurat',
            'Email Login', 'Role',
        ];

        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getCell("{$col}1")->setValue($h);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 2;
        foreach ($employees as $emp) {
            $data = [
                $emp->id,
                $emp->employee_code,
                $emp->nik,
                $emp->ktp_number,
                $emp->full_name,
                $emp->nickname,
                $emp->gender,
                $emp->place_of_birth,
                $emp->date_of_birth?->format('Y-m-d'),
                $emp->marital_status,
                $emp->religion,
                $emp->blood_type,
                $emp->phone,
                $emp->address,
                $emp->city,
                $emp->province,
                $emp->postal_code,
                $emp->company?->name,
                $emp->department?->name,
                $emp->position?->name,
                $emp->jobLevel?->name,                          // ← tambah
                $emp->jobGrade?->name
                    ? "{$emp->jobGrade->name} ({$emp->jobGrade->code})"
                    : null,                                      // ← tambah
                $emp->status?->label,
                $emp->employment_type,
                $emp->join_date?->format('Y-m-d'),
                $emp->contract_end_date?->format('Y-m-d'),
                $emp->resign_date?->format('Y-m-d'),
                $emp->npwp,
                $emp->bpjs_kesehatan,
                $emp->bpjs_ketenagakerjaan,
                $emp->bank_name,
                $emp->bank_account_number,
                $emp->bank_account_name,
                $emp->last_education,
                $emp->last_education_major,
                $emp->last_education_institution,
                $emp->emergency_contact_name,
                $emp->emergency_contact_phone,
                $emp->emergency_contact_relation,
                $emp->user?->email,
                $emp->user?->role,
            ];

            foreach ($data as $colIdx => $value) {
                $col = Coordinate::stringFromColumnIndex($colIdx + 1);
                $sheet->getCell("{$col}{$row}")->setValue($value);
            }

            $rowStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
                'font' => ['size' => 9],
            ];
            if ($row % 2 === 0) {
                $rowStyle['fill'] = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EBF3FB']];
            }
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($rowStyle);
            $row++;
        }

        foreach (range(1, count($headers)) as $colIdx) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIdx))->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $filename = 'employees_'.now()->format('Ymd_His').'.xlsx';

        return response()->stream(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // ── DOWNLOAD TEMPLATE ─────────────────────────────────────────────────────

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->generateTemplateDynamic();
    }

    private function generateTemplateDynamic(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Karyawan');

        $headers = [
            ['label' => 'Nama Lengkap*',                                            'required' => true],
            ['label' => 'Nama Panggilan',                                           'required' => false],
            ['label' => 'Kode Karyawan',                                            'required' => false],
            ['label' => 'NIK (Internal)*',                                          'required' => true],
            ['label' => 'Nomor KTP',                                                'required' => false],
            ['label' => 'Jenis Kelamin (male/female)*',                             'required' => true],
            ['label' => 'Tempat Lahir',                                             'required' => false],
            ['label' => 'Tanggal Lahir (YYYY-MM-DD)',                               'required' => false],
            ['label' => 'Status Menikah (single/married/divorced/widowed)',         'required' => false],
            ['label' => 'Agama',                                                    'required' => false],
            ['label' => 'Golongan Darah',                                           'required' => false],
            ['label' => 'No. HP',                                                   'required' => false],
            ['label' => 'Alamat',                                                   'required' => false],
            ['label' => 'Kota',                                                     'required' => false],
            ['label' => 'Provinsi',                                                 'required' => false],
            ['label' => 'Kode Pos',                                                 'required' => false],
            ['label' => 'Tipe Kepegawaian (permanent/contract/intern/freelance/evaluation)*',  'required' => true],
            ['label' => 'Tanggal Bergabung (YYYY-MM-DD)',                           'required' => false],
            ['label' => 'Akhir Kontrak (YYYY-MM-DD)',                               'required' => false],
            ['label' => 'NPWP',                                                     'required' => false],
            ['label' => 'BPJS Kesehatan',                                           'required' => false],
            ['label' => 'BPJS Ketenagakerjaan',                                     'required' => false],
            ['label' => 'Nama Bank',                                                'required' => false],
            ['label' => 'No. Rekening',                                             'required' => false],
            ['label' => 'Nama Pemilik Rekening',                                   'required' => false],
            ['label' => 'Pendidikan Terakhir (sd/smp/sma/d1/d2/d3/d4/s1/s2/s3)',  'required' => false],
            ['label' => 'Jurusan',                                                  'required' => false],
            ['label' => 'Institusi Pendidikan',                                    'required' => false],
            ['label' => 'Nama Kontak Darurat',                                     'required' => false],
            ['label' => 'No. HP Kontak Darurat',                                   'required' => false],
            ['label' => 'Hubungan Kontak Darurat',                                 'required' => false],
            ['label' => 'ID Perusahaan',                                           'required' => false],
            ['label' => 'ID Departemen',                                           'required' => false],
            ['label' => 'ID Jabatan',                                              'required' => false],
            ['label' => 'ID Job Level',                                            'required' => false],  // ← tambah
            ['label' => 'ID Job Grade',                                            'required' => false],  // ← tambah
            ['label' => 'Email (untuk akun login)',                                'required' => false],
            ['label' => 'Password (min. 6 karakter)',                              'required' => false],
            ['label' => 'Role (employee/admin/hrd/manager)',                       'required' => false],
        ];

        foreach ($headers as $i => $h) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getCell("{$col}1")->setValue($h['label']);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $h['required'] ? '1F3864' : '2E75B6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getColumnDimension($col)->setWidth(max(18, mb_strlen($h['label']) * 1.1));
        }

        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->freezePane('A2');

        return response()->stream(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="employee_import_template.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // ── IMPORT ────────────────────────────────────────────────────────────────

    public function import(Request $request)
    {
        ini_set('memory_limit', '256M');  // ← tambah ini

        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        $reader = IOFactory::createReaderForFile($request->file('file')->getPathname());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();

        if ($highestRow < 2) {
            return response()->json(['message' => 'File kosong atau tidak ada data.'], 422);
        }

        $rows = $sheet->rangeToArray("A1:{$highestCol}{$highestRow}", null, true, true, false);

        $headerRow = array_map(fn ($v) => trim((string) $v), $rows[0]);
        $dataRows = array_slice($rows, 1);
        $dataRows = array_filter(
            $dataRows,
            fn ($row) => ! empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))
        );

        if (count($dataRows) > 500) {
            return response()->json(['message' => 'Maksimal 500 baris per upload.'], 422);
        }

        $colMap = array_flip($headerRow);
        $get = function (array $row, string $key, $default = null) use ($colMap) {
            $idx = $colMap[$key] ?? null;
            if ($idx === null) {
                return $default;
            }
            $v = $row[$idx];

            return ($v === '' || $v === null) ? $default : $this->cleanString(trim((string) $v));
        };

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::beginTransaction();
        try {
            foreach ($dataRows as $lineNumber => $row) {
                $rowNum = $lineNumber + 2;

                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $fullName = $get($row, 'Nama Lengkap*');
                $nik = $get($row, 'NIK (Internal)*');
                $gender = $get($row, 'Jenis Kelamin (male/female)*');
                $employmentType = $get($row, 'Tipe Kepegawaian (permanent/contract/intern/freelance/evaluation)*');

                $companyId = $this->resolveId(Company::class, $get($row, 'ID Perusahaan'));
                $departmentId = $this->resolveId(Department::class, $get($row, 'ID Departemen'));
                $positionId = $this->resolveId(Position::class, $get($row, 'ID Jabatan'));
                $jobLevelId = $this->resolveId(JobLevel::class, $get($row, 'ID Job Level'));  // ← tambah
                $jobGradeId = $this->resolveId(JobGrade::class, $get($row, 'ID Job Grade'));  // ← tambah

                $validator = Validator::make(
                    compact('fullName', 'nik', 'gender', 'employmentType', 'companyId'),
                    [
                        'fullName' => 'required|string|max:200',
                        'nik' => ['required', 'string', Rule::unique('employees', 'nik')],
                        'gender' => ['required', Rule::in(['male', 'female'])],
                        'employmentType' => ['required', Rule::in(['permanent', 'contract', 'intern', 'freelance', 'evaluation'])],
                        'companyId' => 'required|integer',
                    ],
                    [
                        'companyId.required' => 'ID Perusahaan wajib diisi.',
                        'companyId.integer' => 'ID Perusahaan tidak valid.',
                    ]
                );

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowNum,
                        'name' => $fullName ?? '(kosong)',
                        'errors' => $validator->errors()->all(),
                    ];

                    continue;
                }

                $employeeData = array_filter([
                    'full_name' => $fullName,
                    'nickname' => $get($row, 'Nama Panggilan'),
                    'employee_code' => $get($row, 'Kode Karyawan'),
                    'nik' => $nik,
                    'ktp_number' => $get($row, 'Nomor KTP'),
                    'gender' => $gender,
                    'place_of_birth' => $get($row, 'Tempat Lahir'),
                    'date_of_birth' => $this->parseDate($get($row, 'Tanggal Lahir (YYYY-MM-DD)')),
                    'marital_status' => $get($row, 'Status Menikah (single/married/divorced/widowed)'),
                    'religion' => $get($row, 'Agama'),
                    'blood_type' => $get($row, 'Golongan Darah'),
                    'phone' => $get($row, 'No. HP'),
                    'address' => $get($row, 'Alamat'),
                    'city' => $get($row, 'Kota'),
                    'province' => $get($row, 'Provinsi'),
                    'postal_code' => $get($row, 'Kode Pos'),
                    'employment_type' => $employmentType,
                    'join_date' => $this->parseDate($get($row, 'Tanggal Bergabung (YYYY-MM-DD)')),
                    'contract_end_date' => $this->parseDate($get($row, 'Akhir Kontrak (YYYY-MM-DD)')),
                    'npwp' => $get($row, 'NPWP'),
                    'bpjs_kesehatan' => $get($row, 'BPJS Kesehatan'),
                    'bpjs_ketenagakerjaan' => $get($row, 'BPJS Ketenagakerjaan'),
                    'bank_name' => $get($row, 'Nama Bank'),
                    'bank_account_number' => $get($row, 'No. Rekening'),
                    'bank_account_name' => $get($row, 'Nama Pemilik Rekening'),
                    'last_education' => $get($row, 'Pendidikan Terakhir (sd/smp/sma/d1/d2/d3/d4/s1/s2/s3)'),
                    'last_education_major' => $get($row, 'Jurusan'),
                    'last_education_institution' => $get($row, 'Institusi Pendidikan'),
                    'emergency_contact_name' => $get($row, 'Nama Kontak Darurat'),
                    'emergency_contact_phone' => $get($row, 'No. HP Kontak Darurat'),
                    'emergency_contact_relation' => $get($row, 'Hubungan Kontak Darurat'),
                    'company_id' => $companyId,
                    'department_id' => $departmentId,
                    'position_id' => $positionId,
                    'job_level_id' => $jobLevelId,   // ← tambah
                    'job_grade_id' => $jobGradeId,   // ← tambah
                ], fn ($v) => $v !== null);

                $email = $get($row, 'Email (untuk akun login)');
                $password = $get($row, 'Password (min. 6 karakter)');
                $role = $get($row, 'Role (employee/admin/hrd/manager)') ?? 'employee';

                $userData = [
                    'name' => $fullName,
                    'password' => bcrypt($password ?? str()->random(12)),
                    'role' => in_array($role, ['employee', 'admin', 'hrd', 'manager']) ? $role : 'employee',
                ];
                if ($email) {
                    $userData['email'] = $email;
                }

                $user = \App\Models\User::create($userData);
                $employeeData['user_id'] = $user->id;

                Employee::create($employeeData);
                $results['success']++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Import gagal: '.$e->getMessage(), 'results' => $results], 500);
        }

        return response()->json([
            'message' => "Import selesai: {$results['success']} berhasil, {$results['failed']} gagal.",
            'results' => $results,
        ], $results['failed'] > 0 ? 207 : 200);
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $value);

        return $value === '' ? null : $value;
    }

    private function resolveId(string $modelClass, mixed $value): ?int
    {
        if (! $value || ! is_numeric($value)) {
            return null;
        }

        return $modelClass::find((int) $value)?->id;
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
