@extends('layouts.admin')

@section('title', 'Detail Karyawan')

@section('content')
    <div class="flex items-center gap-2 mb-5 text-xs text-gray-400">
        <a href="{{ route('admin.karyawan.index') }}" class="hover:text-gray-600 transition-colors">Karyawan</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600 truncate">{{ $karyawan->full_name }}</span>
    </div>

    {{-- Header Profil --}}
    <div
        class="bg-white border border-gray-100 rounded-2xl p-6 mb-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)] flex flex-col sm:flex-row items-center sm:items-start gap-5">
        @if ($karyawan->photo_url)
            <img src="{{ $karyawan->photo_url }}" alt="{{ $karyawan->full_name }}"
                onclick="openLightbox('{{ $karyawan->photo_url }}')"
                class="w-24 h-24 rounded-2xl object-cover border border-gray-100 flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity">
        @else
            <div
                class="w-24 h-24 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                {{ strtoupper(substr($karyawan->full_name ?? '?', 0, 2)) }}
            </div>
        @endif

        <div class="flex-1 text-center sm:text-left">
            <h2 class="text-lg font-bold text-gray-800">{{ $karyawan->full_name }}</h2>
            <p class="text-[13px] text-gray-400 mt-0.5">{{ $karyawan->position->name ?? '-' }} ·
                {{ $karyawan->department->name ?? '-' }}</p>
            <div class="flex items-center justify-center sm:justify-start gap-2 mt-3 flex-wrap">
                @if (is_null($karyawan->resign_date))
                    <span
                        class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Aktif</span>
                @else
                    <span
                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">Nonaktif</span>
                @endif
                @if ($karyawan->employee_code)
                    <span
                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-50 text-gray-500 font-medium">{{ $karyawan->employee_code }}</span>
                @endif
                @if ($karyawan->company?->name)
                    <span
                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-50 text-gray-500 font-medium">{{ $karyawan->company->name }}</span>
                @endif
            </div>
        </div>

        <div class="flex gap-2 flex-shrink-0">
            <a href="{{ route('admin.karyawan.edit', $karyawan->id) }}"
                class="px-4 py-2.5 rounded-xl text-[12.5px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                style="background:#f97316">
                Edit Data
            </a>
        </div>
    </div>

    @php
        $sections = [
            'Identitas' => [
                'NIK' => $karyawan->nik,
                'No. KTP' => $karyawan->ktp_number,
                'Nama Panggilan' => $karyawan->nickname,
                'Jenis Kelamin' =>
                    $karyawan->gender == 'male' ? 'Laki-laki' : ($karyawan->gender == 'female' ? 'Perempuan' : '-'),
                'Tempat, Tanggal Lahir' => trim(
                    ($karyawan->place_of_birth ?? '-') . ', ' . ($karyawan->date_of_birth?->format('d M Y') ?? '-'),
                ),
                'Status Pernikahan' => ucfirst($karyawan->marital_status ?? '-'),
                'Agama' => $karyawan->religion,
                'Golongan Darah' => $karyawan->blood_type,
            ],
            'Kepegawaian' => [
                'Departemen' => $karyawan->department->name ?? '-',
                'Posisi' => $karyawan->position->name ?? '-',
                'Job Level' => $karyawan->jobLevel->name ?? '-',
                'Job Grade' => $karyawan->jobGrade
                    ? $karyawan->jobGrade->name . ' (' . $karyawan->jobGrade->code . ')'
                    : '-',
                'Tipe Kepegawaian' => ucfirst($karyawan->employment_type ?? '-'),
                'Status Karyawan' => $karyawan->status->label ?? '-',
                'Tanggal Bergabung' => $karyawan->join_date?->format('d M Y') ?? '-',
                'Akhir Kontrak' => $karyawan->contract_end_date?->format('d M Y') ?? '-',
                'Tanggal Resign' => $karyawan->resign_date?->format('d M Y') ?? '-',
                'Email Akun' => $karyawan->user->email ?? '-',
                'Role' => $karyawan->user->role ?? '-',
            ],
            'Kontak' => [
                'No. Telepon' => $karyawan->phone,
                'Alamat' => $karyawan->address,
                'Kota' => $karyawan->city,
                'Provinsi' => $karyawan->province,
                'Kode Pos' => $karyawan->postal_code,
                'Kontak Darurat' => trim(
                    ($karyawan->emergency_contact_name ?? '-') .
                        ' (' .
                        ($karyawan->emergency_contact_relation ?? '-') .
                        ') · ' .
                        ($karyawan->emergency_contact_phone ?? '-'),
                ),
            ],
            'Lainnya' => [
                'NPWP' => $karyawan->npwp,
                'BPJS Kesehatan' => $karyawan->bpjs_kesehatan,
                'BPJS Ketenagakerjaan' => $karyawan->bpjs_ketenagakerjaan,
                'Bank' => $karyawan->bank_name,
                'No. Rekening' => $karyawan->bank_account_number,
                'Atas Nama' => $karyawan->bank_account_name,
                'Pendidikan Terakhir' => strtoupper($karyawan->last_education ?? '-'),
                'Jurusan' => $karyawan->last_education_major,
                'Institusi' => $karyawan->last_education_institution,
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @foreach ($sections as $title => $fields)
            <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                    <p class="text-xs font-semibold text-gray-700">{{ $title }}</p>
                </div>
                <div class="space-y-3">
                    @foreach ($fields as $label => $value)
                        <div class="flex justify-between gap-3 text-[12.5px]">
                            <span class="text-gray-400">{{ $label }}</span>
                            <span class="text-gray-700 font-medium text-right">{{ $value ?: '-' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div id="photo-lightbox" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm"
        onclick="closeLightbox()">
        <img id="photo-lightbox-img" src="" alt="" class="max-w-[90vw] max-h-[90vh] rounded-2xl shadow-2xl"
            onclick="event.stopPropagation()">
        <button onclick="closeLightbox()"
            class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <script>
        function openLightbox(url) {
            document.getElementById('photo-lightbox-img').src = url;
            var lightbox = document.getElementById('photo-lightbox');
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
        }

        function closeLightbox() {
            var lightbox = document.getElementById('photo-lightbox');
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
        }
    </script>

    <div class="mt-5">
        <a href="{{ route('admin.karyawan.index') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
            ← Kembali ke Daftar Karyawan
        </a>
    </div>


@endsection
