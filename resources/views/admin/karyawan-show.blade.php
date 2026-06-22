@extends('layouts.admin')
@section('title', 'Detail Karyawan')

@section('content')
    <div class="max-w-3xl">
        <div class="flex items-center gap-2 mb-6 text-[13px] text-gray-400">
            <a href="{{ route('admin.karyawan.index') }}"
                class="hover:text-gray-600 transition-colors font-medium">Karyawan</a>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-600 font-medium">{{ $karyawan->full_name }}</span>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
            <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                        {{ strtoupper(substr($karyawan->full_name, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="text-[16px] font-bold text-gray-800">{{ $karyawan->full_name }}</h3>
                        <p class="text-[13px] text-gray-400">{{ $karyawan->nik ?? '-' }} ·
                            {{ $karyawan->position?->name ?? '-' }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.karyawan.edit', $karyawan->id) }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-[12.5px] font-semibold text-white hover:opacity-90 transition-opacity"
                    style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
                    Edit
                </a>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-[13px]">
                <div>
                    <p class="text-gray-400 mb-1">Departemen</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->department?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Job Grade</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->jobGrade?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Tipe Kepegawaian</p>
                    <p class="font-medium text-gray-800">{{ ucfirst($karyawan->employment_type) }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Status</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->status?->label ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Tanggal Bergabung</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->join_date?->format('d M Y') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">No. Telepon</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->phone ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Email</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->user?->email ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-1">Alamat</p>
                    <p class="font-medium text-gray-800">{{ $karyawan->address ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
