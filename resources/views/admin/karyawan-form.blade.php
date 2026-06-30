@extends('layouts.admin')

@section('title', isset($karyawan) ? 'Edit Karyawan' : 'Tambah Karyawan')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <div>

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 mb-5 text-xs text-gray-400">
            <a href="{{ route('admin.karyawan.index') }}" class="hover:text-gray-600 transition-colors">Karyawan</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span
                class="text-gray-600 truncate">{{ isset($karyawan) ? 'Edit — ' . $karyawan->full_name : 'Tambah Karyawan' }}</span>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-5 bg-rose-50 border border-rose-100 rounded-2xl px-4 py-3.5 flex gap-3">
                <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <ul class="text-xs text-rose-600 space-y-0.5 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 bg-rose-50 border border-rose-100 rounded-2xl px-4 py-3.5 flex gap-3">
                <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-rose-600">{{ session('error') }}</p>
            </div>
        @endif

        <form method="POST"
            action="{{ isset($karyawan) ? route('admin.karyawan.update', $karyawan->id) : route('admin.karyawan.store') }}"
            id="employee-form">
            @csrf
            @if (isset($karyawan))
                @method('PUT')
            @endif

            {{-- Tab Navigation --}}
            <div class="flex gap-1 mb-0 border-b border-gray-200 overflow-x-auto scrollbar-none -mx-1 px-1">
                @php
                    $tabs = [
                        'identitas' => [
                            'label' => 'Identitas',
                            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        ],
                        'kepegawaian' => [
                            'label' => 'Kepegawaian',
                            'icon' =>
                                'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                        ],
                        'kontak' => [
                            'label' => 'Kontak',
                            'icon' =>
                                'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                        ],
                        'lainnya' => [
                            'label' => 'Lainnya',
                            'icon' =>
                                'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        ],
                    ];
                @endphp

                @foreach ($tabs as $key => $tab)
                    <button type="button" onclick="switchTab('{{ $key }}')" id="tab-{{ $key }}"
                        class="tab-btn flex items-center gap-1.5 px-4 py-3 text-xs font-medium border-b-2 transition-all -mb-px whitespace-nowrap flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}" />
                        </svg>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- ================================================================ --}}
            {{-- TAB: IDENTITAS --}}
            {{-- ================================================================ --}}
            <div id="panel-identitas"
                class="tab-panel bg-white border border-gray-100 border-t-0 rounded-b-2xl rounded-tr-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="sm:col-span-2">
                        @include('admin.karyawan-form-field', [
                            'label' => 'Nama Lengkap',
                            'required' => true,
                            'type' => 'text',
                            'name' => 'full_name',
                            'value' => old('full_name', $karyawan->full_name ?? ''),
                        ])
                    </div>

                    @include('admin.karyawan-form-field', [
                        'label' => 'Nama Panggilan',
                        'type' => 'text',
                        'name' => 'nickname',
                        'value' => old('nickname', $karyawan->nickname ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'NIK',
                        'required' => true,
                        'type' => 'text',
                        'name' => 'nik',
                        'value' => old('nik', $karyawan->nik ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'No. KTP',
                        'type' => 'text',
                        'name' => 'ktp_number',
                        'value' => old('ktp_number', $karyawan->ktp_number ?? ''),
                    ])

                    <div>
                        <label class="form-label">Jenis Kelamin <span class="text-rose-400">*</span></label>
                        <select name="gender" class="form-select">
                            <option value="">-- Pilih --</option>
                            <option value="male" {{ old('gender', $karyawan->gender ?? '') == 'male' ? 'selected' : '' }}>
                                Laki-laki
                            </option>
                            <option value="female"
                                {{ old('gender', $karyawan->gender ?? '') == 'female' ? 'selected' : '' }}>Perempuan
                            </option>
                        </select>
                        @error('gender')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @include('admin.karyawan-form-field', [
                        'label' => 'Tempat Lahir',
                        'type' => 'text',
                        'name' => 'place_of_birth',
                        'value' => old('place_of_birth', $karyawan->place_of_birth ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'Tanggal Lahir',
                        'type' => 'date',
                        'name' => 'date_of_birth',
                        'value' => old(
                            'date_of_birth',
                            isset($karyawan->date_of_birth) ? $karyawan->date_of_birth->format('Y-m-d') : ''),
                    ])

                    <div>
                        <label class="form-label">Status Pernikahan</label>
                        <select name="marital_status" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach (['single' => 'Belum Menikah', 'married' => 'Menikah', 'divorced' => 'Cerai', 'widowed' => 'Janda/Duda'] as $v => $l)
                                <option value="{{ $v }}"
                                    {{ old('marital_status', $karyawan->marital_status ?? '') == $v ? 'selected' : '' }}>
                                    {{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Agama</label>
                        <select name="religion" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $r)
                                <option value="{{ $r }}"
                                    {{ old('religion', $karyawan->religion ?? '') == $r ? 'selected' : '' }}>
                                    {{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Golongan Darah</label>
                        <select name="blood_type" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach (['A', 'B', 'AB', 'O'] as $bt)
                                <option value="{{ $bt }}"
                                    {{ old('blood_type', $karyawan->blood_type ?? '') == $bt ? 'selected' : '' }}>
                                    {{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- TAB: KEPEGAWAIAN --}}
            {{-- ================================================================ --}}
            <div id="panel-kepegawaian"
                class="tab-panel hidden bg-white border border-gray-100 border-t-0 rounded-b-2xl rounded-tr-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    @include('admin.karyawan-form-field', [
                        'label' => 'Kode Karyawan',
                        'type' => 'text',
                        'name' => 'employee_code',
                        'value' => old('employee_code', $karyawan->employee_code ?? ''),
                    ])

                    <div>
                        <label class="form-label">Company <span class="text-rose-400">*</span></label>
                        <select name="company_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}"
                                    {{ old('company_id', $karyawan->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Departemen</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}"
                                    {{ old('department_id', $karyawan->department_id ?? '') == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Posisi / Jabatan</label>
                        <select name="position_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($positions as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('position_id', $karyawan->position_id ?? '') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Job Level</label>
                        <select name="job_level_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($jobLevels as $jl)
                                <option value="{{ $jl->id }}"
                                    {{ old('job_level_id', $karyawan->job_level_id ?? '') == $jl->id ? 'selected' : '' }}>
                                    {{ $jl->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Job Grade</label>
                        <select name="job_grade_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($jobGrades as $jg)
                                <option value="{{ $jg->id }}"
                                    {{ old('job_grade_id', $karyawan->job_grade_id ?? '') == $jg->id ? 'selected' : '' }}>
                                    {{ $jg->name }} ({{ $jg->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Tipe Kepegawaian <span class="text-rose-400">*</span></label>
                        <select name="employment_type" class="form-select">
                            @foreach (['permanent' => 'Tetap', 'contract' => 'Kontrak', 'intern' => 'Intern', 'freelance' => 'Freelance', 'evaluation' => 'Evaluasi'] as $v => $l)
                                <option value="{{ $v }}"
                                    {{ old('employment_type', $karyawan->employment_type ?? 'permanent') == $v ? 'selected' : '' }}>
                                    {{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Status Karyawan</label>
                        <select name="employee_status_id" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($employeeStatuses as $es)
                                <option value="{{ $es->id }}"
                                    {{ old('employee_status_id', $karyawan->employee_status_id ?? '') == $es->id ? 'selected' : '' }}>
                                    {{ $es->label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @include('admin.karyawan-form-field', [
                        'label' => 'Tanggal Bergabung',
                        'type' => 'date',
                        'name' => 'join_date',
                        'value' => old(
                            'join_date',
                            isset($karyawan->join_date) ? $karyawan->join_date->format('Y-m-d') : ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'Akhir Kontrak',
                        'type' => 'date',
                        'name' => 'contract_end_date',
                        'value' => old(
                            'contract_end_date',
                            isset($karyawan->contract_end_date)
                                ? $karyawan->contract_end_date->format('Y-m-d')
                                : ''),
                    ])

                    @isset($karyawan)
                        @include('admin.karyawan-form-field', [
                            'label' => 'Tanggal Resign',
                            'type' => 'date',
                            'name' => 'resign_date',
                            'value' => old(
                                'resign_date',
                                isset($karyawan->resign_date) ? $karyawan->resign_date->format('Y-m-d') : ''),
                        ])
                    @endisset

                </div>

                {{-- Akun Login --}}
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                        <p class="text-xs font-semibold text-gray-700">Akun Login</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @include('admin.karyawan-form-field', [
                            'label' => 'Email',
                            'type' => 'email',
                            'name' => 'email',
                            'value' => old('email', $karyawan->user?->email ?? ''),
                        ])
                        <div>
                            <label class="form-label">
                                Password
                                @isset($karyawan)
                                    <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span>
                                @endisset
                            </label>
                            <input type="password" name="password" class="form-input">
                            @error('password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                @foreach (['employee' => 'Employee', 'hrd' => 'HRD', 'supervisor' => 'Supervisor', 'manager' => 'Manager', 'admin' => 'Admin'] as $v => $l)
                                    <option value="{{ $v }}"
                                        {{ old('role', $karyawan->user?->role ?? 'employee') == $v ? 'selected' : '' }}>
                                        {{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex items-center gap-2.5 pt-1">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                {{ old('is_active', $karyawan->user->is_active ?? true) ? 'checked' : '' }}
                                class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                            <label for="is_active" class="text-xs font-medium text-gray-600">Akun Aktif (nonaktifkan jika
                                ingin menghapus karyawan)</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- TAB: KONTAK --}}
            {{-- ================================================================ --}}
            <div id="panel-kontak"
                class="tab-panel hidden bg-white border border-gray-100 border-t-0 rounded-b-2xl rounded-tr-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @include('admin.karyawan-form-field', [
                        'label' => 'No. Telepon',
                        'type' => 'text',
                        'name' => 'phone',
                        'value' => old('phone', $karyawan->phone ?? ''),
                    ])
                    <div class="sm:col-span-2">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" rows="2" class="form-input resize-none">{{ old('address', $karyawan->address ?? '') }}</textarea>
                    </div>
                    @include('admin.karyawan-form-field', [
                        'label' => 'Kota',
                        'type' => 'text',
                        'name' => 'city',
                        'value' => old('city', $karyawan->city ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'Provinsi',
                        'type' => 'text',
                        'name' => 'province',
                        'value' => old('province', $karyawan->province ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'Kode Pos',
                        'type' => 'text',
                        'name' => 'postal_code',
                        'value' => old('postal_code', $karyawan->postal_code ?? ''),
                    ])
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                        <p class="text-xs font-semibold text-gray-700">Kontak Darurat</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @include('admin.karyawan-form-field', [
                            'label' => 'Nama',
                            'type' => 'text',
                            'name' => 'emergency_contact_name',
                            'value' => old('emergency_contact_name', $karyawan->emergency_contact_name ?? ''),
                        ])
                        @include('admin.karyawan-form-field', [
                            'label' => 'No. Telepon',
                            'type' => 'text',
                            'name' => 'emergency_contact_phone',
                            'value' => old('emergency_contact_phone', $karyawan->emergency_contact_phone ?? ''),
                        ])
                        @include('admin.karyawan-form-field', [
                            'label' => 'Hubungan',
                            'type' => 'text',
                            'name' => 'emergency_contact_relation',
                            'value' => old(
                                'emergency_contact_relation',
                                $karyawan->emergency_contact_relation ?? ''),
                        ])
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- TAB: LAINNYA --}}
            {{-- ================================================================ --}}
            <div id="panel-lainnya"
                class="tab-panel hidden bg-white border border-gray-100 border-t-0 rounded-b-2xl rounded-tr-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @include('admin.karyawan-form-field', [
                        'label' => 'NPWP',
                        'type' => 'text',
                        'name' => 'npwp',
                        'value' => old('npwp', $karyawan->npwp ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'BPJS Kesehatan',
                        'type' => 'text',
                        'name' => 'bpjs_kesehatan',
                        'value' => old('bpjs_kesehatan', $karyawan->bpjs_kesehatan ?? ''),
                    ])
                    @include('admin.karyawan-form-field', [
                        'label' => 'BPJS Ketenagakerjaan',
                        'type' => 'text',
                        'name' => 'bpjs_ketenagakerjaan',
                        'value' => old('bpjs_ketenagakerjaan', $karyawan->bpjs_ketenagakerjaan ?? ''),
                    ])
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                        <p class="text-xs font-semibold text-gray-700">Rekening Bank</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @include('admin.karyawan-form-field', [
                            'label' => 'Nama Bank',
                            'type' => 'text',
                            'name' => 'bank_name',
                            'value' => old('bank_name', $karyawan->bank_name ?? ''),
                        ])
                        @include('admin.karyawan-form-field', [
                            'label' => 'No. Rekening',
                            'type' => 'text',
                            'name' => 'bank_account_number',
                            'value' => old('bank_account_number', $karyawan->bank_account_number ?? ''),
                        ])
                        @include('admin.karyawan-form-field', [
                            'label' => 'Atas Nama',
                            'type' => 'text',
                            'name' => 'bank_account_name',
                            'value' => old('bank_account_name', $karyawan->bank_account_name ?? ''),
                        ])
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                        <p class="text-xs font-semibold text-gray-700">Pendidikan Terakhir</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Jenjang</label>
                            <select name="last_education" class="form-select">
                                <option value="">-- Pilih --</option>
                                @foreach (['sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA/SMK', 'd1' => 'D1', 'd2' => 'D2', 'd3' => 'D3', 'd4' => 'D4', 's1' => 'S1', 's2' => 'S2', 's3' => 'S3'] as $v => $l)
                                    <option value="{{ $v }}"
                                        {{ old('last_education', $karyawan->last_education ?? '') == $v ? 'selected' : '' }}>
                                        {{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('admin.karyawan-form-field', [
                            'label' => 'Jurusan',
                            'type' => 'text',
                            'name' => 'last_education_major',
                            'value' => old('last_education_major', $karyawan->last_education_major ?? ''),
                        ])
                        @include('admin.karyawan-form-field', [
                            'label' => 'Institusi',
                            'type' => 'text',
                            'name' => 'last_education_institution',
                            'value' => old(
                                'last_education_institution',
                                $karyawan->last_education_institution ?? ''),
                        ])
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div
                class="flex items-center gap-4 mt-6 sticky bottom-0 bg-gradient-to-t from-[#FAFAFB] via-[#FAFAFB] to-transparent pt-3 pb-1 sm:static sm:bg-none sm:pt-0">
                <button type="submit"
                    class="flex items-center gap-2 text-xs font-medium px-5 py-2.5 rounded-xl text-white transition-opacity hover:opacity-90 shadow-sm"
                    style="background:#f97316">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isset($karyawan) ? 'Update Data' : 'Simpan Karyawan' }}
                </button>
                <a href="{{ route('admin.karyawan.index') }}"
                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Batal</a>
            </div>

        </form>
    </div>

    <style>
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-none {
            scrollbar-width: none;
        }

        .form-label {
            display: block;
            font-size: 10.5px;
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 4px;
        }

        .form-input {
            width: 100%;
            font-size: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 8px 11px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
        }

        .form-select {
            width: 100%;
            font-size: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 8px 11px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
        }

        .form-error {
            font-size: 10px;
            color: #ef4444;
            margin-top: 4px;
        }

        .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 10px !important;
            padding: 8px 13px !important;
            font-size: 12px !important;
            background: #fff !important;
            min-height: 41px;
        }

        .choices.is-focused .choices__inner {
            border-color: #f97316 !important;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08) !important;
        }

        .choices__list--dropdown {
            border-radius: 10px !important;
            border-color: #E5E7EB !important;
            font-size: 12px !important;
        }

        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }

        .choices__input {
            font-size: 12px !important;
            background: #fff !important;
        }
    </style>
@endsection

@push('scripts')
    <script>
        const tabs = ['identitas', 'kepegawaian', 'kontak', 'lainnya'];

        function switchTab(active) {
            tabs.forEach(tab => {
                const panel = document.getElementById('panel-' + tab);
                const btn = document.getElementById('tab-' + tab);
                if (tab === active) {
                    panel.classList.remove('hidden');
                    btn.style.color = '#f97316';
                    btn.style.borderColor = '#f97316';
                    btn.style.borderBottomWidth = '2px';
                } else {
                    panel.classList.add('hidden');
                    btn.style.color = '#9CA3AF';
                    btn.style.borderColor = 'transparent';
                }
            });
            localStorage.setItem('karyawan_tab', active);
        }

        // Restore tab — error validation atau last visited
        const savedTab = '{{ old('_tab') }}' || localStorage.getItem('karyawan_tab') || 'identitas';

        @if ($errors->any())
            const errorFields = @json($errors->keys());
            const tabMap = {
                identitas: ['full_name', 'nickname', 'nik', 'ktp_number', 'gender', 'place_of_birth', 'date_of_birth',
                    'marital_status', 'religion', 'blood_type'
                ],
                kepegawaian: ['employee_code', 'company_id', 'department_id', 'position_id', 'job_level_id',
                    'job_grade_id',
                    'employment_type', 'employee_status_id', 'join_date', 'contract_end_date', 'resign_date',
                    'email', 'password', 'role'
                ],
                kontak: ['phone', 'address', 'city', 'province', 'postal_code', 'emergency_contact_name',
                    'emergency_contact_phone', 'emergency_contact_relation'
                ],
                lainnya: ['npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'bank_name', 'bank_account_number',
                    'bank_account_name', 'last_education', 'last_education_major', 'last_education_institution'
                ],
            };
            let firstErrorTab = 'identitas';
            for (const [tab, fields] of Object.entries(tabMap)) {
                if (errorFields.some(f => fields.includes(f))) {
                    firstErrorTab = tab;
                    break;
                }
            }
            switchTab(firstErrorTab);
        @else
            switchTab(savedTab);
        @endif

        @if (session('error'))
            <
            div class = "mb-5 bg-rose-50 border border-rose-100 rounded-2xl px-4 py-3.5 flex gap-3" >
            <
            svg class = "w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5"
            fill = "none"
            stroke = "currentColor"
            viewBox = "0 0 24 24" >
                <
                path stroke - linecap = "round"
            stroke - linejoin = "round"
            stroke - width = "2"
            d = "M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" / >
                <
                /svg> <
            p class = "text-xs text-rose-600" > {{ session('error') }} < /p> < /
            div >
        @endif

        // Simpan tab aktif sebelum submit
        document.getElementById('employee-form').addEventListener('submit', () => {
            const active = tabs.find(t => !document.getElementById('panel-' + t).classList.contains('hidden'));
            if (active) localStorage.setItem('karyawan_tab', active);
        });
    </script>
    <script>
        ['toast-success', 'toast-error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                setTimeout(() => {
                    el.style.transition = 'opacity 0.3s';
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                }, 3000);
            }
        });
    </script>
    <script>
        document.querySelectorAll('.form-select').forEach(function(el) {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
            });
        });
    </script>
@endpush
