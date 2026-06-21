@extends('layouts.admin')

@section('title', 'Department')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

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

    @if (session('import_errors') && count(session('import_errors')) > 0)
        <div id="toast-import-errors"
            class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-white border border-amber-100 shadow-xl rounded-xl px-5 py-4 text-[13px] max-w-md w-full mx-4"
            style="animation: slideDown 0.3s ease-out;">
            <p class="font-semibold text-amber-700 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Sebagian baris gagal diimport
            </p>
            <ul class="text-[12px] text-amber-600 space-y-1 list-disc list-inside max-h-32 overflow-y-auto">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-base font-bold text-gray-800">Department</h3>
            <p class="text-[13px] text-gray-400 mt-1 font-medium">Total {{ $departments->total() }} department terdaftar</p>
        </div>
        <div class="flex items-center gap-2.5 flex-shrink-0">
            <button type="button" onclick="openTreeModal()"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13.5px] font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                <span class="hidden sm:inline">Lihat Struktur</span>
            </button>

            <div class="relative">
                <button type="button" onclick="document.getElementById('export-menu').classList.toggle('hidden')"
                    class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13.5px] font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden sm:inline">Excel</span>
                </button>
                <div id="export-menu"
                    class="hidden absolute right-0 mt-2 w-52 bg-white border border-gray-100 rounded-xl shadow-lg z-10 py-1.5">
                    <a href="{{ route('admin.department.export') }}"
                        class="block px-4 py-2.5 text-[13px] text-gray-600 hover:bg-gray-50 transition-colors">Export
                        Data</a>
                    <a href="{{ route('admin.department.import-template') }}"
                        class="block px-4 py-2.5 text-[13px] text-gray-600 hover:bg-gray-50 transition-colors">Download
                        Template</a>
                    <button type="button"
                        onclick="document.getElementById('import-modal').classList.remove('hidden'); document.getElementById('import-modal').classList.add('flex')"
                        class="w-full text-left block px-4 py-2.5 text-[13px] text-gray-600 hover:bg-gray-50 transition-colors">Import
                        Data</button>
                </div>
            </div>

            <a href="{{ route('admin.department.create') }}"
                class="flex items-center gap-2 px-4 py-3 rounded-xl text-[13.5px] font-semibold text-white shadow-[0_4px_14px_rgba(249,115,22,0.35)] hover:opacity-90 transition-opacity"
                style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Tambah Department</span>
                <span class="sm:hidden">Tambah</span>
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-5 mb-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" class="flex flex-wrap items-center gap-3.5">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau kode department..."
                    class="w-full pl-11 pr-4 py-3 text-[13.5px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-3 focus:ring-orange-50 transition-all">
            </div>

            <select name="company_id" class="filter-select text-[13.5px] border border-gray-200 rounded-xl px-4 py-3">
                <option value="">Semua Company</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}</option>
                @endforeach
            </select>

            <button type="submit"
                class="text-[13.5px] font-semibold px-5 py-3 rounded-xl text-white hover:opacity-90 transition-opacity shadow-sm"
                style="background:#0f2d6b">
                Filter
            </button>

            @if (request()->anyFilled(['search', 'company_id']))
                <a href="{{ route('admin.department.index') }}"
                    class="text-[13px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Reset</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70">
                        <th class="text-left px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nama
                            Department</th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            Company</th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Parent
                        </th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            Manager</th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status
                        </th>
                        <th class="text-right px-6 py-4 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $dept)
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                        style="background:#0f2d6b1A; color:#0f2d6b">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />

                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $dept->name }}</p>
                                        @if ($dept->code)
                                            <p class="text-[12px] font-bold text-gray-400 mt-0.5">{{ $dept->code }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-gray-600">{{ $dept->company?->name ?? '-' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $dept->parent?->name ?? '–' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $dept->manager?->full_name ?? '-' }}</td>
                            <td class="px-4 py-4">
                                <button type="button"
                                    onclick="openEmployeesModal({{ $dept->id }}, '{{ addslashes($dept->name) }}')"
                                    class="px-2.5 py-1 rounded-full text-[11.5px] font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors {{ $dept->employees_count == 0 ? 'pointer-events-none bg-gray-100 text-gray-400' : '' }}">
                                    {{ $dept->employees_count }} orang
                                </button>
                            </td>
                            <td class="px-4 py-4">
                                @if ($dept->is_active)
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[11.5px] font-semibold bg-emerald-50 text-emerald-700">Aktif</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[11.5px] font-semibold bg-gray-100 text-gray-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.department.edit', $dept->id) }}"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.department.destroy', $dept->id) }}"
                                        class="delete-department-form">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-rose-600 hover:border-rose-200 transition-colors">
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
                            <td colspan="7" class="py-12 text-center text-gray-400">Tidak ada data department</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($departments->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $departments->links() }}
            </div>
        @endif
    </div>

    {{-- Tree Modal --}}
    <div id="tree-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col"
            style="animation: popIn 0.2s ease-out;">
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 flex-shrink-0">
                <h3 class="text-[15px] font-bold text-gray-800">Struktur Department</h3>
                <button type="button" onclick="closeTreeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="tree-modal-body" class="overflow-y-auto p-5 flex-1">
                <p class="text-center text-[13px] text-gray-400 py-12">Memuat struktur...</p>
            </div>
        </div>
    </div>

    {{-- Employees Modal --}}
    <div id="employees-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[80vh] flex flex-col"
            style="animation: popIn 0.2s ease-out;">
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-[15px] font-bold text-gray-800">Karyawan Department</h3>
                    <p id="employees-modal-subtitle" class="text-[12px] text-gray-400 mt-0.5"></p>
                </div>
                <button type="button" onclick="closeEmployeesModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="employees-modal-body" class="overflow-y-auto px-6 py-2 flex-1">
                <p class="text-center text-[13px] text-gray-400 py-12">Memuat data karyawan...</p>
            </div>
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6" style="animation: popIn 0.2s ease-out;">
            <h3 class="text-[15px] font-bold text-gray-800 mb-4">Import Department</h3>
            <form method="POST" action="{{ route('admin.department.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full text-[13px] border border-gray-200 rounded-xl px-4 py-3 mb-3">
                <p class="text-[12px] text-gray-400 mb-5">Gunakan template yang sudah didownload. Maksimal 5MB.</p>
                <div class="flex gap-3">
                    <button type="button"
                        onclick="document.getElementById('import-modal').classList.add('hidden'); document.getElementById('import-modal').classList.remove('flex')"
                        class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-white hover:opacity-90 transition-opacity"
                        style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
                        Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6 text-center"
            style="animation: popIn 0.2s ease-out;">
            <div class="w-14 h-14 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-[15px] font-bold text-gray-800 mb-1.5">Hapus Department?</h3>
            <p class="text-[13px] text-gray-400 mb-6">Department yang sudah dihapus tidak dapat dikembalikan.</p>
            <div class="flex gap-3">
                <button type="button" id="delete-cancel-btn"
                    class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="button" id="delete-confirm-btn"
                    class="flex-1 py-2.5 rounded-xl text-[13px] font-semibold text-white transition-opacity hover:opacity-90"
                    style="background:#e11d48">
                    Hapus
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
@endsection

@push('scripts')
    <script>
        var treeUrl = "{{ route('admin.department.tree-html') }}";

        function openTreeModal() {
            var modal = document.getElementById('tree-modal');
            var body = document.getElementById('tree-modal-body');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            body.innerHTML = '<p class="text-center text-[13px] text-gray-400 py-12">Memuat struktur...</p>';

            fetch(treeUrl)
                .then(function(res) {
                    return res.text();
                })
                .then(function(html) {
                    body.innerHTML = html;
                });
        }

        function closeTreeModal() {
            var modal = document.getElementById('tree-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        var employeesUrlBase = "{{ url('admin/department') }}";

        function openEmployeesModal(deptId, deptName) {
            var modal = document.getElementById('employees-modal');
            var body = document.getElementById('employees-modal-body');
            var subtitle = document.getElementById('employees-modal-subtitle');

            subtitle.textContent = deptName;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            body.innerHTML = '<p class="text-center text-[13px] text-gray-400 py-12">Memuat data karyawan...</p>';

            fetch(employeesUrlBase + '/' + deptId + '/employees-html')
                .then(function(res) {
                    return res.text();
                })
                .then(function(html) {
                    body.innerHTML = html;
                });
        }

        function closeEmployeesModal() {
            var modal = document.getElementById('employees-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('employees-modal').addEventListener('click', function(e) {
            if (e.target === this) closeEmployeesModal();
        });

        function toggleNode(id) {
            const children = document.getElementById('children-' + id);
            const icon = document.getElementById('icon-' + id);
            if (children) {
                children.classList.toggle('hidden');
                icon.classList.toggle('rotate-90');
            }
        }

        document.getElementById('tree-modal').addEventListener('click', function(e) {
            if (e.target === this) closeTreeModal();
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#export-menu') && !e.target.closest('button[onclick*="export-menu"]')) {
                document.getElementById('export-menu')?.classList.add('hidden');
            }
        });

        ['toast-success', 'toast-error', 'toast-import-errors'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                setTimeout(function() {
                    el.style.transition = 'opacity 0.3s, transform 0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'translate(-50%, -15px)';
                    setTimeout(function() {
                        el.remove();
                    }, 300);
                }, id === 'toast-import-errors' ? 6000 : 3000);
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

            document.querySelectorAll('.delete-department-form').forEach(function(form) {
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
@endpush
