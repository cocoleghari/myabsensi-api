@extends('layouts.admin')

@section('title', 'Karyawan')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    @if (session('success'))
        <div id="toast-success"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white border border-emerald-100 shadow-xl rounded-xl px-4 py-2.5 text-[12.5px] font-medium text-emerald-700"
            style="animation: slideDown 0.3s ease-out;">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div id="toast-error"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-white border border-rose-100 shadow-xl rounded-xl px-4 py-2.5 text-[12.5px] font-medium text-rose-700"
            style="animation: slideDown 0.3s ease-out;">
            <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <style>
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -15px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
    </style>

    <script>
        ['toast-success', 'toast-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                setTimeout(function() {
                    el.style.transition = 'opacity 0.3s, transform 0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'translate(-50%, -15px)';
                    setTimeout(function() {
                        el.remove();
                    }, 300);
                }, 3000);
            }
        });
    </script>

    <div id="photo-preview" class="fixed z-50 hidden pointer-events-none">
        <img id="photo-preview-img" src="" alt=""
            class="w-48 h-48 object-cover rounded-xl shadow-2xl border-4 border-white">
    </div>

    {{-- Header & Tombol Tambah --}}
    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-sm font-bold text-gray-800">Data Karyawan</h3>
            <p class="text-[11.5px] text-gray-400 mt-1 font-medium">Total {{ $karyawan->total() }} karyawan terdaftar</p>
        </div>
        <a href="{{ route('admin.karyawan.create') }}"
            class="flex items-center gap-2 px-3 py-2 rounded-xl text-[12.5px] font-semibold text-white shadow-[0_3px_10px_rgba(249,115,22,0.3)] hover:opacity-90 transition-opacity flex-shrink-0"
            style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Tambah Karyawan</span>
            <span class="sm:hidden">Tambah</span>
        </a>
    </div>

    {{-- Filter / Search --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.karyawan.index') }}" class="flex flex-wrap items-center gap-2.5">
            @if (request('without_shift'))
                <input type="hidden" name="without_shift" value="{{ request('without_shift') }}">
            @endif

            @if (request('without_lokasi'))
                <input type="hidden" name="without_lokasi" value="{{ request('without_lokasi') }}">
            @endif

            <div class="relative flex-1 min-w-[180px]">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, NIK, atau email..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
            </div>

            <select name="department_id"
                class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>

            <select name="status"
                class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
                <option value="">Semua Status</option>
                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit"
                class="text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm"
                style="background:#0f2d6b">
                Filter
            </button>

            @if (request('search') || request('department_id') || request('status'))
                <a href="{{ route('admin.karyawan.index') }}"
                    class="text-[12px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Reset</a>
            @endif
        </form>
    </div>

    @if (request('without_shift') === '1')
        <div class="bg-rose-50 border border-rose-100 rounded-xl px-4 py-3 mb-5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-[13px] text-rose-700 font-medium">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Menampilkan {{ $karyawan->total() }} karyawan yang belum memiliki shift aktif
            </div>
            <a href="{{ route('admin.karyawan.index', request()->except('without_shift')) }}"
                class="text-[12px] text-rose-500 hover:text-rose-700 font-medium flex-shrink-0">
                Hapus filter
            </a>
        </div>
    @endif

    @if (request('without_lokasi') === '1')
        <div class="bg-rose-50 border border-rose-100 rounded-xl px-4 py-3 mb-5 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-[13px] text-rose-700 font-medium">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Menampilkan {{ $karyawan->total() }} karyawan yang belum memiliki lokasi absensi
            </div>
            <a href="{{ route('admin.karyawan.index', request()->except('without_lokasi')) }}"
                class="text-[12px] text-rose-500 hover:text-rose-700 font-medium flex-shrink-0">
                Hapus filter
            </a>
        </div>
    @endif

    @php
        $colors = [
            'bg-blue-50 text-blue-700',
            'bg-emerald-50 text-emerald-700',
            'bg-violet-50 text-violet-700',
            'bg-pink-50 text-pink-700',
            'bg-amber-50 text-amber-700',
        ];
    @endphp

    {{-- Mobile: Card List --}}
    <div class="sm:hidden space-y-3">
        @forelse ($karyawan as $item)
            @php
                $initials = strtoupper(substr($item->full_name ?? '?', 0, 2));
                $color = $colors[$loop->index % count($colors)];
            @endphp
            <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                <div class="flex items-start gap-3">
                    @if ($item->photo_url)
                        <img src="{{ $item->photo_url }}" alt="{{ $item->full_name }}"
                            data-full="{{ $item->photo_url }}"
                            class="employee-photo-thumb cursor-zoom-in w-11 h-11 rounded-full object-cover flex-shrink-0 border border-gray-100">
                    @else
                        <div
                            class="w-11 h-11 rounded-full {{ $color }} flex items-center justify-center text-[13px] font-bold flex-shrink-0">
                            {{ $initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-[14.5px] font-semibold text-gray-800 truncate">{{ $item->full_name }}</p>
                        <p class="text-[12.5px] text-gray-400 mt-0.5">
                            {{ $item->employee_code ?? ($item->user?->email ?? '-') }}</p>
                        <div class="flex items-center gap-2 mt-2.5 flex-wrap">
                            @if (is_null($item->resign_date))
                                <span
                                    class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Aktif</span>
                            @else
                                <span
                                    class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">Nonaktif</span>
                            @endif
                            @if ($item->department?->name)
                                <span
                                    class="text-[11px] px-2.5 py-1 rounded-full bg-gray-50 text-gray-500 font-medium">{{ $item->department->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <a href="{{ route('admin.karyawan.edit', $item->id) }}"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </a>
                        <a href="{{ route('admin.karyawan.show', $item->id) }}"
                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('admin.karyawan.destroy', $item->id) }}"
                            class="delete-employee-form">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div
                class="bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                <p class="text-[13px] text-gray-400">Tidak ada data karyawan</p>
            </div>
        @endforelse
    </div>

    {{-- Desktop: Table --}}
    <div
        class="hidden sm:block bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">NIK
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Departemen</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Posisi
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status
                        </th>
                        <th class="text-right px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($karyawan as $item)
                        @php
                            $initials = strtoupper(substr($item->full_name ?? '?', 0, 2));
                            $color = $colors[$loop->index % count($colors)];
                        @endphp
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3.5">
                                    @if ($item->photo_url)
                                        <img src="{{ $item->photo_url }}" alt="{{ $item->full_name }}"
                                            data-full="{{ $item->photo_url }}"
                                            class="employee-photo-thumb cursor-zoom-in w-10 h-10 rounded-full object-cover flex-shrink-0 border border-gray-100">
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full {{ $color }} flex items-center justify-center text-[12px] font-bold flex-shrink-0">
                                            {{ $initials }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-[13px] font-semibold text-gray-800">{{ $item->full_name }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            {{ $item->employee_code ?? ($item->user?->email ?? '-') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">{{ $item->nik ?? '-' }}</td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ $item->department->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ $item->position->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if (is_null($item->resign_date))
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Aktif</span>
                                @else
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.karyawan.edit', $item->id) }}"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.karyawan.show', $item->id) }}"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.karyawan.destroy', $item->id) }}"
                                        class="delete-employee-form">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-[13px] text-gray-400">Tidak ada data
                                karyawan</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($karyawan->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $karyawan->links() }}
            </div>
        @endif
    </div>

    {{-- Mobile pagination --}}
    @if ($karyawan->hasPages())
        <div class="sm:hidden mt-4">
            {{ $karyawan->links() }}
        </div>
    @endif

    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-5 text-center"
            style="animation: popIn 0.2s ease-out;">
            <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-[13.5px] font-bold text-gray-800 mb-1">Hapus Data Karyawan?</h3>
            <p class="text-[12px] text-gray-400 mb-4">Data yang sudah dihapus tidak dapat dikembalikan. Pastikan kamu yakin
                sebelum melanjutkan.</p>
            <div class="flex gap-3">
                <button type="button" id="delete-cancel-btn"
                    class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="button" id="delete-confirm-btn"
                    class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-white transition-opacity hover:opacity-90"
                    style="background:#e11d48">
                    Hapus
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .filter-select-wrapper {
            margin-bottom: 0 !important;
            width: 180px;
            flex-shrink: 0;
        }

        .filter-select-wrapper .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 12px !important;
            padding: 7px 12px !important;
            font-size: 12.5px !important;
            background: #fff !important;
            min-height: 38px;
            margin: 0 !important;
        }

        .filter-select-wrapper.is-focused .choices__inner {
            border-color: #fb923c !important;
        }

        .filter-select-wrapper .choices__list--dropdown {
            border-radius: 12px !important;
            font-size: 12.5px !important;
        }

        .filter-select-wrapper .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }
    </style>

    <script>
        (function() {
            var modal = document.getElementById('delete-modal');
            var cancelBtn = document.getElementById('delete-cancel-btn');
            var confirmBtn = document.getElementById('delete-confirm-btn');
            var activeForm = null;

            document.querySelectorAll('.delete-employee-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    activeForm = form;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            cancelBtn.addEventListener('click', function() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                activeForm = null;
            });

            confirmBtn.addEventListener('click', function() {
                if (activeForm) activeForm.submit();
            });

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    activeForm = null;
                }
            });
        })();
    </script>
    <script>
        document.querySelectorAll('.filter-select').forEach(function(el) {
            var choice = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
            });
            choice.containerOuter.element.classList.add('filter-select-wrapper');
        });
    </script>
    <script>
        (function() {
            var preview = document.getElementById('photo-preview');
            var previewImg = document.getElementById('photo-preview-img');

            document.querySelectorAll('.employee-photo-thumb').forEach(function(thumb) {
                thumb.addEventListener('mouseenter', function(e) {
                    previewImg.src = thumb.dataset.full;
                    preview.classList.remove('hidden');
                    positionPreview(e);
                });
                thumb.addEventListener('mousemove', positionPreview);
                thumb.addEventListener('mouseleave', function() {
                    preview.classList.add('hidden');
                });
            });

            function positionPreview(e) {
                var offset = 16;
                var x = e.clientX + offset;
                var y = e.clientY + offset;

                if (x + 200 > window.innerWidth) x = e.clientX - 200 - offset;
                if (y + 200 > window.innerHeight) y = e.clientY - 200 - offset;

                preview.style.left = x + 'px';
                preview.style.top = y + 'px';
            }
        })();
    </script>
@endsection
