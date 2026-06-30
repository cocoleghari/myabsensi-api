@extends('layouts.admin')
@section('title', 'Akses Ditolak')
@section('content')
    <div class="flex flex-col items-center justify-center py-24">
        <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-6V7m0 0a2 2 0 00-2-2H8a2 2 0 00-2 2v1m8-1a2 2 0 012 2v1M6 8H4a2 2 0 00-2 2v8a2 2 0 002 2h16a2 2 0 002-2v-8a2 2 0 00-2-2h-2" />
            </svg>
        </div>
        <h2 class="text-[18px] font-bold text-gray-800 mb-2">Akses Ditolak</h2>
        <p class="text-[14px] text-gray-400 mb-6">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="{{ route('admin.dashboard') }}" class="text-[13.5px] font-semibold px-5 py-2.5 rounded-xl text-white"
            style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
            Kembali ke Dashboard
        </a>
    </div>
@endsection
