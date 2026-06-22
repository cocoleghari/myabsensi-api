@extends('layouts.admin')

@section('title', 'Master Shift')

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

    <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="min-w-0">
            <h3 class="text-sm font-bold text-gray-800">Master Shift</h3>
            <p class="text-[11.5px] text-gray-400 mt-1 font-medium">Total {{ $shifts->total() }} shift terdaftar</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <button type="button" onclick="openImportModal()"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                Import
            </button>
            <a href="{{ route('admin.master-shift.export', request()->query()) }}"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13px] font-semibold text-white shadow-sm hover:opacity-90 transition-opacity"
                style="background:#0f2d6b">
                Export
            </a>
            <a href="{{ route('admin.master-shift.create') }}"
                class="flex items-center gap-2 px-3 py-2 rounded-xl text-[12.5px] font-semibold text-white shadow-[0_3px_10px_rgba(249,115,22,0.3)] hover:opacity-90 transition-opacity"
                style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Tambah Shift</span>
                <span class="sm:hidden">Tambah</span>
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.master-shift.index') }}" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px]">
                <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau kode shift..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
            </div>

            <select name="company_id" class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Company</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}</option>
                @endforeach
            </select>

            <select name="status" class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Status</option>
                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>

            <button type="submit"
                class="text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm"
                style="background:#0f2d6b">
                Filter
            </button>

            @if (request()->anyFilled(['search', 'company_id', 'status']))
                <a href="{{ route('admin.master-shift.index') }}"
                    class="text-[12px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Shift
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Company
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Jam
                            Kerja</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Toleransi</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status
                        </th>
                        <th class="text-right px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-semibold text-gray-800">{{ $shift->nama }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $shift->kode }}</p>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ $shift->company?->name ?? '-' }}</td>
                            <td class="px-4 py-4 text-[13px] text-gray-600 font-medium">
                                {{ $shift->jam_masuk }} - {{ $shift->jam_pulang }}
                                @if ($shift->melewati_tengah_malam)
                                    <span class="text-[10.5px] text-violet-600 font-semibold">(lewat tengah malam)</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-[13px] text-gray-600">{{ $shift->toleransi_terlambat_menit }} mnt
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-[11px] px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">
                                    {{ $shift->employee_shifts_count }} orang
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($shift->is_active)
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Aktif</span>
                                @else
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.master-shift.edit', $shift->id) }}"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.master-shift.destroy', $shift->id) }}"
                                        class="delete-shift-form">
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
                            <td colspan="7" class="px-4 py-12 text-center text-[13px] text-gray-400">Tidak ada data
                                shift</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shifts->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Delete --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-5 text-center"
            style="animation: popIn 0.2s ease-out;">
            <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-[13.5px] font-bold text-gray-800 mb-1">Hapus Shift?</h3>
            <p class="text-[12px] text-gray-400 mb-4">Shift yang masih digunakan karyawan tidak dapat dihapus.</p>
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

    {{-- Modal Import --}}
    <div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6" style="animation: popIn 0.2s ease-out;">
            <h3 class="text-[15px] font-bold text-gray-800 mb-1">Import Shift</h3>
            <p class="text-[12.5px] text-gray-400 mb-4">
                Unggah file Excel sesuai template. <a href="{{ route('admin.master-shift.import-template') }}"
                    class="text-blue-600 hover:underline font-semibold">Unduh template</a>.
            </p>

            <input type="file" id="import-file-input" accept=".xlsx,.xls"
                class="w-full text-[12.5px] border border-gray-200 rounded-xl px-3 py-2.5 mb-3 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-orange-50 file:text-orange-700 file:text-[12px] file:font-semibold">

            <div id="import-result" class="hidden mb-3 max-h-[200px] overflow-y-auto"></div>

            <div class="flex gap-3">
                <button type="button" onclick="closeImportModal()"
                    class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Tutup
                </button>
                <button type="button" id="import-submit-btn" onclick="submitImport()"
                    class="flex-1 py-2 rounded-xl text-[12.5px] font-semibold text-white transition-opacity hover:opacity-90"
                    style="background:#f97316">
                    Upload &amp; Import
                </button>
            </div>
        </div>
    </div>

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

        document.querySelectorAll('.filter-select').forEach(function(el) {
            var choice = new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
            choice.containerOuter.element.classList.add('filter-select-wrapper');
        });

        (function() {
            var modal = document.getElementById('delete-modal');
            var cancelBtn = document.getElementById('delete-cancel-btn');
            var confirmBtn = document.getElementById('delete-confirm-btn');
            var activeForm = null;

            document.querySelectorAll('.delete-shift-form').forEach(function(form) {
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

        function openImportModal() {
            document.getElementById('import-file-input').value = '';
            document.getElementById('import-result').classList.add('hidden');
            document.getElementById('import-result').innerHTML = '';
            var modal = document.getElementById('import-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeImportModal() {
            var modal = document.getElementById('import-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            location.reload();
        }

        function submitImport() {
            var fileInput = document.getElementById('import-file-input');
            var resultEl = document.getElementById('import-result');
            var btn = document.getElementById('import-submit-btn');

            if (!fileInput.files.length) {
                alert('Pilih file Excel terlebih dahulu.');
                return;
            }

            var formData = new FormData();
            formData.append('file', fileInput.files[0]);

            btn.disabled = true;
            btn.textContent = 'Mengupload...';

            fetch("{{ route('admin.master-shift.import') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData,
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(json) {
                    btn.disabled = false;
                    btn.textContent = 'Upload & Import';
                    resultEl.classList.remove('hidden');

                    var html = '<div class="bg-gray-50 rounded-xl p-3 text-[12.5px] text-gray-700 font-medium mb-2">' +
                        json.message + '</div>';
                    if (json.errors && json.errors.length > 0) {
                        html += '<ul class="text-[11.5px] text-rose-600 space-y-1 list-disc list-inside">';
                        json.errors.forEach(function(err) {
                            html += '<li>' + err + '</li>';
                        });
                        html += '</ul>';
                    }
                    resultEl.innerHTML = html;
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.textContent = 'Upload & Import';
                    resultEl.classList.remove('hidden');
                    resultEl.innerHTML =
                        '<div class="bg-rose-50 text-rose-600 rounded-xl p-3 text-[12.5px]">Gagal mengupload file.</div>';
                });
        }
    </script>
@endsection
