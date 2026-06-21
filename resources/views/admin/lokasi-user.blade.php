@extends('layouts.admin')

@section('title', 'Lokasi User')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    {{-- Toast --}}
    @if (session('success'))
        <div id="toast-success"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white border border-emerald-100 shadow-xl rounded-xl px-5 py-3.5 text-[13px] font-medium text-emerald-700"
            style="animation: slideDown 0.3s ease-out;">
            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div id="toast-error"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white border border-rose-100 shadow-xl rounded-xl px-5 py-3.5 text-[13px] font-medium text-rose-700"
            style="animation: slideDown 0.3s ease-out;">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-base font-bold text-gray-800">Lokasi User</h3>
            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-1">
                <p class="text-[13px] text-gray-400 font-medium">Total {{ $employees->total() }} karyawan memiliki lokasi
                    absensi</p>
                @if ($employeesWithoutLocation > 0)
                    <a href="{{ route('admin.karyawan.index', ['without_lokasi' => 1]) }}"
                        class="inline-flex items-center gap-1.5 text-[12px] px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 font-semibold hover:bg-rose-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $employeesWithoutLocation }} karyawan belum punya lokasi
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-5 mb-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.lokasi-user') }}" class="flex flex-wrap items-center gap-3.5">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="w-[18px] h-[18px] absolute left-4 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, NIK, atau kode karyawan..."
                    class="w-full pl-11 pr-4 py-3 text-[13.5px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-3 focus:ring-orange-50 transition-all">
            </div>

            <select name="pusat_lokasi_id"
                class="filter-select text-[13.5px] border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-orange-400">
                <option value="">Semua Lokasi</option>
                @foreach ($lokasis as $lok)
                    <option value="{{ $lok->id }}" {{ request('pusat_lokasi_id') == $lok->id ? 'selected' : '' }}>
                        {{ $lok->nama_lokasi }}
                    </option>
                @endforeach
            </select>

            <select name="department_id"
                class="filter-select text-[13.5px] border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-orange-400">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="text-[13.5px] font-semibold px-5 py-3 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm"
                style="background:#0f2d6b">Filter</button>

            @if (request()->anyFilled(['search', 'pusat_lokasi_id', 'department_id']))
                <a href="{{ route('admin.lokasi-user') }}"
                    class="text-[13px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            Departemen</th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Jumlah
                            Lokasi</th>
                        <th class="text-right px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $emp)
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            {{-- Karyawan --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-semibold text-[12px] flex-shrink-0">
                                        {{ strtoupper(substr($emp->full_name ?? '?', 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[13.5px] font-semibold text-gray-800 truncate">{{ $emp->full_name }}
                                        </p>
                                        <p class="text-[12px] text-gray-400 mt-0.5">
                                            {{ $emp->nik ?? '-' }} · {{ $emp->position?->name ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            {{-- Departemen --}}
                            <td class="px-4 py-4 text-[13px] text-gray-600">
                                {{ $emp->department?->name ?? '-' }}
                            </td>
                            {{-- Jumlah Lokasi --}}
                            <td class="px-4 py-4">
                                <button type="button"
                                    onclick="openDetailModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}')"
                                    class="inline-flex items-center gap-1.5 text-[12px] px-3 py-1.5 rounded-full font-semibold transition-colors cursor-pointer
                                        {{ $emp->pusat_lokasis_count > 0 ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'bg-gray-100 text-gray-400' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    {{ $emp->pusat_lokasis_count }} lokasi
                                </button>
                            </td>
                            {{-- Aksi --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Tambah Lokasi --}}
                                    <button type="button"
                                        onclick="openAddModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}')"
                                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 text-[12px] font-semibold text-gray-500 hover:text-orange-600 hover:border-orange-200 hover:bg-orange-50 transition-colors"
                                        title="Tambah Lokasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span class="hidden sm:inline">Tambah Lokasi</span>
                                    </button>
                                    {{-- Hapus Semua --}}
                                    <button type="button"
                                        onclick="openDeleteAllModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}')"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-colors"
                                        title="Hapus Semua Lokasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-gray-400">
                                    <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    <p class="text-[13.5px]">Belum ada karyawan dengan lokasi absensi</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    {{-- ── Modal: Detail Lokasi Karyawan ──────────────────────────────────── --}}
    <div id="detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm"
        onclick="closeOnBackdrop(event,'detail-modal')">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4 max-h-[80vh] flex flex-col"
            style="animation: popIn 0.2s ease-out;">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3 flex-shrink-0">
                <div class="min-w-0">
                    <h3 class="text-[14.5px] font-bold text-gray-800" id="detail-modal-title">Detail Lokasi</h3>
                    <p class="text-[12px] text-gray-400 mt-0.5" id="detail-modal-sub">-</p>
                </div>
                <button type="button" onclick="closeModal('detail-modal')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="detail-modal-list" class="flex-1 overflow-y-auto divide-y divide-gray-50">
                <p class="px-5 py-10 text-center text-[13px] text-gray-400">Memuat data...</p>
            </div>
        </div>
    </div>

    {{-- ── Modal: Tambah Lokasi ke Karyawan (multi-select) ───────────────── --}}
    <div id="add-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm"
        onclick="closeOnBackdrop(event,'add-modal')">
        <div class="bg-white rounded-2xl shadow-xl w-full mx-4 flex flex-col"
            style="max-width:500px; max-height:86vh; animation: popIn 0.2s ease-out;">

            {{-- Header --}}
            <div class="p-5 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-[14.5px] font-bold text-gray-800">Tambah Lokasi</h3>
                    <p class="text-[12px] text-gray-400 mt-0.5" id="add-modal-sub">-</p>
                </div>
                <button type="button" onclick="closeModal('add-modal')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.lokasi-user.store-multiple') }}" id="add-lokasi-form"
                class="flex flex-col flex-1 min-h-0">
                @csrf
                <input type="hidden" name="employee_id" id="add-employee-id">

                {{-- Search --}}
                <div class="px-5 pt-4 pb-3 flex-shrink-0">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" id="add-lokasi-search" placeholder="Cari nama lokasi..."
                            class="w-full pl-10 pr-4 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
                    </div>
                </div>

                {{-- Select all bar --}}
                <div class="px-5 pb-2 flex items-center justify-between flex-shrink-0">
                    <span class="text-[12px] text-gray-400" id="add-selected-count">0 dipilih</span>
                    <div class="flex gap-3">
                        <button type="button" onclick="selectAllVisible()"
                            class="text-[12px] text-blue-600 font-semibold hover:underline">Pilih Semua</button>
                        <span class="text-gray-300 text-[12px]">|</span>
                        <button type="button" onclick="deselectAll()"
                            class="text-[12px] text-gray-400 font-semibold hover:underline">Reset</button>
                    </div>
                </div>

                {{-- Lokasi multi-checkbox cards --}}
                <div id="add-lokasi-list" class="flex-1 overflow-y-auto px-5 pb-4 space-y-2">
                    @foreach ($lokasis as $lok)
                        <label
                            class="lokasi-card flex items-center gap-3.5 p-3.5 rounded-xl border border-gray-200 cursor-pointer transition-all hover:border-orange-300 hover:bg-orange-50/30 select-none"
                            data-nama="{{ strtolower($lok->nama_lokasi) }}">
                            {{-- Hidden checkbox --}}
                            <input type="checkbox" name="pusat_lokasi_ids[]" value="{{ $lok->id }}"
                                class="lokasi-cb sr-only" onchange="onCbChange(this)">
                            {{-- Custom checkbox visual --}}
                            <div
                                class="lokasi-chk w-5 h-5 rounded-md border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-all">
                                <svg class="lokasi-chk-icon w-3 h-3 text-white opacity-0 transition-opacity"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            {{-- Icon --}}
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                style="background:#EFF6FF">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <span
                                class="lokasi-nama text-[13.5px] font-medium text-gray-700 flex-1 leading-snug">{{ $lok->nama_lokasi }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="px-5 pb-5 pt-4 flex gap-3 flex-shrink-0 border-t border-gray-100">
                    <button type="button" onclick="closeModal('add-modal')"
                        class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="add-submit-btn" disabled
                        class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-white transition-all opacity-40 cursor-not-allowed"
                        style="background:linear-gradient(135deg,#f97316,#ea580c)">
                        <span id="add-submit-label">Simpan Lokasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Modal: Hapus Semua Lokasi ───────────────────────────────────────── --}}
    <div id="delete-all-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm"
        onclick="closeOnBackdrop(event,'delete-all-modal')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6 text-center"
            style="animation: popIn 0.2s ease-out;">
            <div class="w-14 h-14 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-[15px] font-bold text-gray-800 mb-1.5">Hapus Semua Lokasi?</h3>
            <p class="text-[13px] text-gray-400 mb-1" id="delete-all-name"></p>
            <p class="text-[12px] text-gray-400 mb-6">Karyawan tidak akan bisa absen di lokasi manapun setelah ini.</p>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('delete-all-modal')"
                    class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <form id="delete-all-form" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="w-full py-2.5 px-6 rounded-xl text-[13px] font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background:#e11d48">
                        Hapus Semua
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modal: Hapus Satu Pivot ─────────────────────────────────────────── --}}
    <div id="delete-pivot-modal"
        class="fixed inset-0 z-60 hidden items-center justify-center bg-black/50 backdrop-blur-sm"
        onclick="closeOnBackdrop(event,'delete-pivot-modal')">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6 text-center"
            style="animation: popIn 0.2s ease-out;">
            <div class="w-14 h-14 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                </svg>
            </div>
            <h3 class="text-[15px] font-bold text-gray-800 mb-1.5">Hapus Lokasi Ini?</h3>
            <p class="text-[13px] text-gray-500 mb-6" id="delete-pivot-name"></p>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('delete-pivot-modal')"
                    class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <form id="delete-pivot-form" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="w-full py-2.5 px-6 rounded-xl text-[13px] font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background:#e11d48">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -15px)
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0)
            }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.95)
            }

            to {
                opacity: 1;
                transform: scale(1)
            }
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            font-size: 13px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 10px 13px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
        }

        .z-60 {
            z-index: 60;
        }

        /* Lokasi card multi-select */
        .lokasi-card.checked {
            border-color: #f97316 !important;
            background: #fff7ed !important;
        }

        .lokasi-card.checked .lokasi-chk {
            border-color: #f97316 !important;
            background: #f97316 !important;
        }

        .lokasi-card.checked .lokasi-chk-icon {
            opacity: 1 !important;
        }

        .lokasi-card.checked .lokasi-nama {
            color: #c2410c !important;
            font-weight: 600 !important;
        }

        /* filter choices */
        .filter-select-wrapper {
            margin-bottom: 0 !important;
            width: 200px;
            flex-shrink: 0;
        }

        .filter-select-wrapper .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 12px !important;
            padding: 10px 16px !important;
            font-size: 13.5px !important;
            background: #fff !important;
            min-height: 46px;
            margin: 0 !important;
        }

        .filter-select-wrapper.is-focused .choices__inner {
            border-color: #fb923c !important;
        }

        .filter-select-wrapper .choices__list--dropdown {
            border-radius: 12px !important;
            font-size: 13.5px !important;
        }

        .filter-select-wrapper .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }
    </style>

    <script>
        // Toast dismiss
        ['toast-success', 'toast-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            setTimeout(function() {
                el.style.transition = 'opacity .3s,transform .3s';
                el.style.opacity = '0';
                el.style.transform = 'translate(-50%,-15px)';
                setTimeout(function() {
                    el.remove()
                }, 300);
            }, 3000);
        });

        // Choices filter
        document.querySelectorAll('.filter-select').forEach(function(el) {
            var c = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
            c.containerOuter.element.classList.add('filter-select-wrapper');
        });

        // Modal helpers
        function closeModal(id) {
            var m = document.getElementById(id);
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        function openModal(id) {
            var m = document.getElementById(id);
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function closeOnBackdrop(e, id) {
            if (e.target.id === id) closeModal(id);
        }

        // ── Detail Modal ─────────────────────────────────────────────────────
        var detailUrl = '{{ url('admin/lokasi-user') }}';

        function openDetailModal(employeeId, employeeName) {
            document.getElementById('detail-modal-title').textContent = employeeName;
            document.getElementById('detail-modal-sub').textContent = 'Memuat lokasi...';
            document.getElementById('detail-modal-list').innerHTML =
                '<p class="px-5 py-10 text-center text-[13px] text-gray-400">Memuat data...</p>';
            openModal('detail-modal');

            fetch(detailUrl + '/' + employeeId + '/detail')
                .then(function(r) {
                    return r.json();
                })
                .then(function(json) {
                    document.getElementById('detail-modal-sub').textContent = json.data.length + ' lokasi terdaftar';
                    renderDetailList(json.data);
                });
        }

        function renderDetailList(data) {
            var el = document.getElementById('detail-modal-list');
            if (data.length === 0) {
                el.innerHTML = '<p class="px-5 py-10 text-center text-[13px] text-gray-400">Belum ada lokasi terdaftar</p>';
                return;
            }
            el.innerHTML = data.map(function(item) {
                return '<div class="flex items-center gap-4 px-5 py-4">' +
                    '<div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#EFF6FF">' +
                    '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                    '<p class="text-[13.5px] font-semibold text-gray-800">' + item.nama_lokasi + '</p>' +
                    '<p class="text-[11.5px] text-gray-400 mt-0.5">' +
                    'Radius: <strong>' + (item.radius_meter || 100) + ' m</strong>' +
                    (item.keterangan ? ' · ' + item.keterangan : '') +
                    '</p>' +
                    (item.koordinat && item.koordinat !== '-' ?
                        '<a href="https://www.google.com/maps?q=' + item.koordinat +
                        '" target="_blank" class="text-[11px] text-blue-500 hover:underline">' + item.koordinat +
                        '</a>' :
                        '') +
                    '</div>' +
                    '<button type="button" onclick="openDeletePivotModal(' + item.pivot_id + ', \'' + item
                    .nama_lokasi.replace(/'/g, "\\'") + '\')" ' +
                    'class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-colors flex-shrink-0">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                    '</button>' +
                    '</div>';
            }).join('');
        }

        // ── Add Modal (multi-select) ─────────────────────────────────────────
        function onCbChange(cb) {
            var card = cb.closest('.lokasi-card');
            if (cb.checked) {
                card.classList.add('checked');
            } else {
                card.classList.remove('checked');
            }
            updateAddCounter();
        }

        function updateAddCounter() {
            var checked = document.querySelectorAll('.lokasi-cb:checked').length;
            var countEl = document.getElementById('add-selected-count');
            var btn = document.getElementById('add-submit-btn');
            var label = document.getElementById('add-submit-label');

            countEl.textContent = checked + ' dipilih';

            if (checked > 0) {
                btn.disabled = false;
                btn.classList.remove('opacity-40', 'cursor-not-allowed');
                btn.classList.add('hover:opacity-90');
                label.textContent = 'Simpan ' + checked + ' Lokasi';
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-40', 'cursor-not-allowed');
                btn.classList.remove('hover:opacity-90');
                label.textContent = 'Simpan Lokasi';
            }
        }

        function selectAllVisible() {
            document.querySelectorAll('.lokasi-card').forEach(function(card) {
                if (card.style.display === 'none') return;
                var cb = card.querySelector('.lokasi-cb');
                cb.checked = true;
                card.classList.add('checked');
            });
            updateAddCounter();
        }

        function deselectAll() {
            document.querySelectorAll('.lokasi-cb').forEach(function(cb) {
                cb.checked = false;
            });
            document.querySelectorAll('.lokasi-card').forEach(function(c) {
                c.classList.remove('checked');
            });
            updateAddCounter();
        }

        function openAddModal(employeeId, employeeName) {
            document.getElementById('add-employee-id').value = employeeId;
            document.getElementById('add-modal-sub').textContent = employeeName;
            // Reset
            deselectAll();
            document.getElementById('add-lokasi-search').value = '';
            document.querySelectorAll('.lokasi-card').forEach(function(c) {
                c.style.display = '';
            });
            openModal('add-modal');
        }

        // Search filter
        document.getElementById('add-lokasi-search').addEventListener('input', function() {
            var term = this.value.toLowerCase();
            document.querySelectorAll('.lokasi-card').forEach(function(card) {
                card.style.display = (card.getAttribute('data-nama') || '').includes(term) ? '' : 'none';
            });
        });

        // ── Delete All Modal ─────────────────────────────────────────────────
        function openDeleteAllModal(employeeId, employeeName) {
            document.getElementById('delete-all-name').textContent = employeeName;
            document.getElementById('delete-all-form').action = '{{ url('admin/lokasi-user') }}/' + employeeId +
                '/delete-all';
            openModal('delete-all-modal');
        }

        // ── Delete Pivot Modal ───────────────────────────────────────────────
        function openDeletePivotModal(pivotId, namaLokasi) {
            document.getElementById('delete-pivot-name').textContent = namaLokasi;
            document.getElementById('delete-pivot-form').action = '{{ url('admin/lokasi-user/pivot') }}/' + pivotId;
            openModal('delete-pivot-modal');
        }
    </script>
@endsection
