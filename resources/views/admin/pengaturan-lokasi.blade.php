@extends('layouts.admin')

@section('title', 'Pengaturan Lokasi')

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

    <div class="flex items-center justify-between mb-6 gap-3">
        <div class="min-w-0">
            <h3 class="text-sm font-bold text-gray-800">Pengaturan Lokasi</h3>
            <p class="text-[11.5px] text-gray-400 mt-1 font-medium">Total {{ $lokasi->total() }} pusat lokasi terdaftar</p>
        </div>
        <a href="{{ route('admin.pengaturan-lokasi.create') }}"
            class="flex items-center gap-2 px-3 py-2 rounded-xl text-[12.5px] font-semibold text-white shadow-[0_3px_10px_rgba(249,115,22,0.3)] hover:opacity-90 transition-opacity flex-shrink-0"
            style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Tambah Lokasi</span>
            <span class="sm:hidden">Tambah</span>
        </a>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="GET" action="{{ route('admin.pengaturan-lokasi.index') }}"
            class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px]">
                <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama lokasi atau keterangan..."
                    class="w-full pl-10 pr-4 py-2 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-50 transition-all">
            </div>

            <select name="company_id" class="filter-select text-[12.5px] border border-gray-200 rounded-xl px-3 py-2">
                <option value="">Semua Company</option>
                @foreach ($companies as $c)
                    <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
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
                <a href="{{ route('admin.pengaturan-lokasi.index') }}"
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
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nama
                            Lokasi</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Company
                        </th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Koordinat</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            Karyawan</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status
                        </th>
                        <th class="text-right px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lokasi as $item)
                        <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <p class="text-[13px] font-semibold text-gray-800">{{ $item->nama_lokasi }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5 max-w-[220px] truncate">
                                    {{ $item->keterangan ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-gray-600 font-medium">
                                {{ $item->company?->name ?? '-' }}</td>
                            <td class="px-4 py-4 text-[13px]">
                                @if ($item->titik_kordinat)
                                    <a href="https://www.google.com/maps?q={{ $item->titik_kordinat }}" target="_blank"
                                        class="text-blue-600 hover:underline">{{ $item->titik_kordinat }}</a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button type="button"
                                    onclick="openEmployeeModal('{{ route('admin.pengaturan-lokasi.employees', $item->id) }}', '{{ $item->nama_lokasi }}')"
                                    class="text-[11px] px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition-colors cursor-pointer">
                                    {{ $item->employees_count }} orang
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                @if ($item->is_active)
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold">Aktif</span>
                                @else
                                    <span
                                        class="text-[11px] px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 font-semibold">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pengaturan-lokasi.assign', $item->id) }}"
                                        title="Assign Karyawan"
                                        class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-violet-600 hover:border-violet-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.pengaturan-lokasi.edit', $item->id) }}"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST"
                                        action="{{ route('admin.pengaturan-lokasi.destroy', $item->id) }}"
                                        class="delete-lokasi-form">
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
                                pusat lokasi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lokasi->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $lokasi->links() }}
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
            <h3 class="text-[13.5px] font-bold text-gray-800 mb-1">Hapus Pusat Lokasi?</h3>
            <p class="text-[12px] text-gray-400 mb-4">Lokasi yang masih digunakan karyawan tidak dapat dihapus.</p>
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

    <div id="employee-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm"
        onclick="closeEmployeeModalOnBackdrop(event)">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 max-h-[80vh] flex flex-col"
            style="animation: popIn 0.2s ease-out;">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3 flex-shrink-0">
                <div class="min-w-0">
                    <h3 class="text-[14.5px] font-bold text-gray-800 truncate" id="employee-modal-title">Karyawan</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5" id="employee-modal-count">-</p>
                </div>
                <button type="button" onclick="closeEmployeeModal()"
                    class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4 border-b border-gray-100 flex-shrink-0">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" id="employee-modal-search" placeholder="Cari nama karyawan..."
                        class="w-full pl-10 pr-4 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 transition-all">
                </div>
            </div>

            <div id="employee-modal-list" class="flex-1 overflow-y-auto divide-y divide-gray-50">
                <p class="px-5 py-10 text-center text-[13px] text-gray-400">Memuat data...</p>
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

            document.querySelectorAll('.delete-lokasi-form').forEach(function(form) {
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
        var employeeModalData = [];

        function openEmployeeModal(url, namaLokasi) {
            var modal = document.getElementById('employee-modal');
            var listEl = document.getElementById('employee-modal-list');
            var titleEl = document.getElementById('employee-modal-title');
            var countEl = document.getElementById('employee-modal-count');
            var searchInput = document.getElementById('employee-modal-search');

            titleEl.textContent = 'Karyawan — ' + namaLokasi;
            countEl.textContent = 'Memuat...';
            searchInput.value = '';
            listEl.innerHTML = '<p class="px-5 py-10 text-center text-[13px] text-gray-400">Memuat data...</p>';

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            fetch(url)
                .then(function(res) {
                    return res.json();
                })
                .then(function(json) {
                    employeeModalData = json.data;
                    countEl.textContent = json.data.length + ' karyawan terdaftar';
                    renderEmployeeModalList(employeeModalData);
                });
        }

        function renderEmployeeModalList(data) {
            var listEl = document.getElementById('employee-modal-list');

            if (data.length === 0) {
                listEl.innerHTML =
                    '<p class="px-5 py-10 text-center text-[13px] text-gray-400">Belum ada karyawan di lokasi ini</p>';
                return;
            }

            listEl.innerHTML = data.map(function(emp) {
                return '<div class="px-5 py-3">' +
                    '<p class="text-[13px] font-medium text-gray-800">' + emp.full_name + '</p>' +
                    '<p class="text-[11px] text-gray-400 mt-0.5">' + emp.position_name + ' · ' + emp
                    .department_name + '</p>' +
                    '</div>';
            }).join('');
        }

        document.getElementById('employee-modal-search').addEventListener('input', function(e) {
            var term = e.target.value.toLowerCase();
            var filtered = employeeModalData.filter(function(emp) {
                return emp.full_name.toLowerCase().includes(term);
            });
            renderEmployeeModalList(filtered);
        });

        function closeEmployeeModal() {
            var modal = document.getElementById('employee-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function closeEmployeeModalOnBackdrop(e) {
            if (e.target.id === 'employee-modal') closeEmployeeModal();
        }
    </script>
@endsection
