@extends('layouts.admin')
@section('title', 'Assign Shift Baru')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="flex items-center gap-2 mb-6 text-xs text-gray-400">
        <a href="{{ route('admin.assign-shift.index') }}" class="hover:text-gray-600 transition">Assign Shift</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600">Assign Shift Baru</span>
    </div>

    @if ($errors->any())
        <div class="mb-5 bg-red-50 border border-red-100 rounded-xl px-4 py-3 flex gap-3">
            <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <ul class="text-xs text-red-600 space-y-0.5 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.assign-shift.store') }}" id="assign-form">
        @csrf
        <div id="hidden-inputs-container"></div>

        <div class="flex flex-col lg:flex-row gap-5">

            {{-- Sidebar config --}}
            <div class="lg:w-[300px] flex-shrink-0">
                <div class="lg:sticky lg:top-24 space-y-4">

                    {{-- Pilih Shift --}}
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                            <p class="text-xs font-semibold text-gray-700">Pilih Shift</p>
                        </div>

                        {{-- Tab Switcher --}}
                        <div class="grid grid-cols-2 border border-gray-200 rounded-xl overflow-hidden mb-4">
                            <button type="button" id="tab-shift"
                                class="tab-btn flex items-center justify-center gap-1.5 py-2 text-[11.5px] font-semibold transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Shift Kerja
                            </button>
                            <button type="button" id="tab-pattern"
                                class="tab-btn flex items-center justify-center gap-1.5 py-2 text-[11.5px] font-semibold transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Pola Mingguan
                            </button>
                        </div>

                        <div id="panel-shift">
                            <label class="form-label">Shift Kerja</label>
                            <select name="shift_id" class="choices-select form-input">
                                <option value="">-- Pilih Shift --</option>
                                @foreach ($shifts as $s)
                                    <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->nama }} ({{ $s->kode }}) · {{ $s->jam_masuk }} –
                                        {{ $s->jam_pulang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($patterns->isNotEmpty())
                            <div id="panel-pattern" style="display:none;">
                                <label class="form-label">Pola Shift Mingguan</label>
                                <select name="pattern_id" class="choices-select form-input">
                                    <option value="">-- Pilih Pola --</option>
                                    @foreach ($patterns as $p)
                                        <option value="{{ $p->id }}"
                                            {{ old('pattern_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->nama }} ({{ $p->kode }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    {{-- Konfigurasi Tanggal --}}
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                            <p class="text-xs font-semibold text-gray-700">Periode</p>
                        </div>

                        <label class="form-label">Tanggal Mulai <span class="text-red-400">*</span></label>
                        <input type="date" name="tanggal_mulai"
                            value="{{ old('tanggal_mulai', now()->toDateString()) }}" class="form-input mb-3">

                        <label class="form-label">Tanggal Selesai <span
                                class="text-gray-400 font-normal">(opsional)</span></label>
                        <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}"
                            class="form-input mb-3">

                        <label class="form-label">Keterangan <span
                                class="text-gray-400 font-normal">(opsional)</span></label>
                        <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="form-input">
                    </div>

                    {{-- Counter --}}
                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                        <p class="text-[12px] text-gray-400 mb-1">Karyawan Terpilih</p>
                        <p class="text-[28px] font-bold leading-none" style="color:#f97316" id="selected-count">0</p>
                        <p class="text-[11px] text-gray-400 mt-1">Assignment lama akan ditutup otomatis</p>
                    </div>

                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 text-[13px] font-semibold px-5 py-3 rounded-xl text-white transition-opacity hover:opacity-90"
                        style="background:#f97316">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Assignment
                    </button>
                    <a href="{{ route('admin.assign-shift.index') }}"
                        class="block text-center text-[12.5px] text-gray-400 hover:text-gray-600 transition">Batal</a>
                </div>
            </div>

            {{-- Main list karyawan --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-[0_2px_8px_rgba(16,24,40,0.05)]">

                    <div class="p-5 border-b border-gray-100 space-y-3">
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" id="employee-search" placeholder="Cari nama atau kode karyawan..."
                                class="w-full pl-10 pr-4 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 transition-all">
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <select id="department-filter" multiple
                                class="choices-select text-[13px] border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                            <select id="position-filter" multiple
                                class="choices-select text-[13px] border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
                                @foreach ($positions as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <select id="job-level-filter" multiple
                                class="choices-select text-[13px] border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400">
                                @foreach ($jobLevels as $jl)
                                    <option value="{{ $jl->id }}">{{ $jl->name }}</option>
                                @endforeach
                            </select>

                            <div class="flex items-center gap-2 text-[12px] ml-auto">
                                <button type="button" id="select-all-btn"
                                    class="text-blue-600 hover:underline font-medium whitespace-nowrap">Pilih Semua
                                    Hasil</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" id="deselect-all-btn"
                                    class="text-gray-500 hover:underline font-medium whitespace-nowrap">Hapus
                                    Semua</button>
                            </div>
                        </div>
                    </div>

                    <div id="employee-list" class="divide-y divide-gray-50 min-h-[300px]">
                        <p class="px-5 py-12 text-center text-[13px] text-gray-400">Memuat data karyawan...</p>
                    </div>

                    <div class="p-4 border-t border-gray-100 flex items-center justify-between gap-3">
                        <p class="text-[12px] text-gray-400" id="pagination-info">-</p>
                        <div class="flex items-center gap-2">
                            <button type="button" id="prev-btn"
                                class="px-3.5 py-2 rounded-lg text-[12px] font-semibold border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">←
                                Sebelumnya</button>
                            <button type="button" id="next-btn"
                                class="px-3.5 py-2 rounded-lg text-[12px] font-semibold border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">Selanjutnya
                                →</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style>
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
            transition: border-color .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .08);
        }

        .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 10px !important;
            padding: 8px 13px !important;
            font-size: 13px !important;
            background: #fff !important;
            min-height: 42px;
        }

        .choices__inner:focus-within,
        .is-focused .choices__inner {
            border-color: #f97316 !important;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .08) !important;
        }

        .choices__list--dropdown {
            border-radius: 10px !important;
            font-size: 13px !important;
        }

        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }

        .choices__list--multiple .choices__item {
            background: #fff7ed !important;
            border: 1px solid #fed7aa !important;
            color: #ea580c !important;
            font-size: 12px !important;
            border-radius: 6px !important;
        }

        .choices__list--multiple .choices__item .choices__button {
            border-left: 1px solid #fed7aa !important;
        }

        .tab-btn {
            background: #F9FAFB;
            color: #6B7280;
            border: none;
            cursor: pointer;
        }

        .tab-btn.active {
            background: #fff7ed;
            color: #ea580c;
        }
    </style>
@endsection

@push('scripts')
    <script>
        (function() {
            var searchUrl = "{{ route('admin.assign-shift.search') }}";
            var allIdsUrl = "{{ route('admin.assign-shift.all-ids') }}";
            var selectedMap = new Map();
            var currentPage = 1,
                lastPage = 1;
            var filters = {
                search: '',
                department_id: [],
                position_id: [],
                job_level_id: []
            };
            var debounce = null;

            var listEl = document.getElementById('employee-list');
            var countEl = document.getElementById('selected-count');
            var infoEl = document.getElementById('pagination-info');
            var prevBtn = document.getElementById('prev-btn');
            var nextBtn = document.getElementById('next-btn');

            function updateCount() {
                countEl.textContent = selectedMap.size;
            }

            function renderRow(emp) {
                var checked = selectedMap.has(emp.id) ? 'checked' : '';
                var row = document.createElement('label');
                row.className =
                    'flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/60 cursor-pointer transition-colors';
                row.innerHTML =
                    '<input type="checkbox" data-id="' + emp.id + '" ' + checked +
                    ' class="emp-cb w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400 flex-shrink-0">' +
                    '<div class="flex-1 min-w-0">' +
                    '<p class="text-[13px] font-medium text-gray-800">' + emp.full_name + '</p>' +
                    '<p class="text-[12px] text-gray-400">' + (emp.nik || '-') + ' · ' + emp.department_name +
                    '</p>' +
                    '</div>';
                row.querySelector('.emp-cb').addEventListener('change', function(e) {
                    if (e.target.checked) selectedMap.set(emp.id, true);
                    else selectedMap.delete(emp.id);
                    updateCount();
                });
                return row;
            }

            function buildParams(page) {
                var params = new URLSearchParams();
                params.set('page', page);
                if (filters.search) params.set('search', filters.search);
                ['department_id', 'position_id', 'job_level_id'].forEach(function(key) {
                    filters[key].forEach(function(val) {
                        params.append(key + '[]', val);
                    });
                });
                return params;
            }

            function fetchEmployees(page) {
                currentPage = page;
                listEl.innerHTML =
                    '<p class="px-5 py-12 text-center text-[13px] text-gray-400">Memuat data karyawan...</p>';
                fetch(searchUrl + '?' + buildParams(page))
                    .then(r => r.json())
                    .then(json => {
                        listEl.innerHTML = '';
                        if (!json.data.length) {
                            listEl.innerHTML =
                                '<p class="px-5 py-12 text-center text-[13px] text-gray-400">Tidak ada karyawan ditemukan</p>';
                        } else {
                            json.data.forEach(emp => listEl.appendChild(renderRow(emp)));
                        }
                        lastPage = json.last_page;
                        infoEl.textContent = 'Halaman ' + json.current_page + ' dari ' + json.last_page + ' (' +
                            json.total + ' karyawan)';
                        prevBtn.disabled = json.current_page <= 1;
                        nextBtn.disabled = json.current_page >= json.last_page;
                    });
            }

            document.getElementById('employee-search').addEventListener('input', function(e) {
                clearTimeout(debounce);
                debounce = setTimeout(() => {
                    filters.search = e.target.value;
                    fetchEmployees(1);
                }, 350);
            });

            // Tab switcher Shift Kerja vs Pola Mingguan
            (function() {
                var tabShift = document.getElementById('tab-shift');
                var tabPattern = document.getElementById('tab-pattern');
                var panelShift = document.getElementById('panel-shift');
                var panelPattern = document.getElementById('panel-pattern');

                if (!tabShift || !tabPattern) return;

                var shiftSelect = panelShift.querySelector('select[name="shift_id"]');
                var patternSelect = panelPattern ? panelPattern.querySelector('select[name="pattern_id"]') : null;

                function activate(tab) {
                    var isShift = tab === 'shift';
                    panelShift.style.display = isShift ? '' : 'none';
                    if (panelPattern) panelPattern.style.display = isShift ? 'none' : '';
                    tabShift.classList.toggle('active', isShift);
                    tabPattern.classList.toggle('active', !isShift);

                    if (isShift && patternSelect) {
                        patternSelect.value = '';
                        if (patternSelect.choicesInstance) patternSelect.choicesInstance.setChoiceByValue('');
                    } else if (!isShift && shiftSelect) {
                        shiftSelect.value = '';
                        if (shiftSelect.choicesInstance) shiftSelect.choicesInstance.setChoiceByValue('');
                    }
                }

                tabShift.addEventListener('click', function() {
                    activate('shift');
                });
                tabPattern.addEventListener('click', function() {
                    activate('pattern');
                });

                activate('shift'); // default
            })();

            // Init Choices untuk filter multi-select
            var filterMap = {
                'department-filter': 'department_id',
                'position-filter': 'position_id',
                'job-level-filter': 'job_level_id'
            };
            var placeholderMap = {
                'department-filter': 'Semua Departemen',
                'position-filter': 'Semua Posisi',
                'job-level-filter': 'Semua Job Level'
            };

            Object.entries(filterMap).forEach(([elId, key]) => {
                var el = document.getElementById(elId);
                var choice = new Choices(el, {
                    removeItemButton: true,
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: placeholderMap[elId],
                    searchPlaceholderValue: 'Cari...'
                });
                el.addEventListener('change', function() {
                    filters[key] = Array.from(el.selectedOptions).map(o => o.value);
                    fetchEmployees(1);
                });
            });

            // Init Choices untuk Pilih Shift & Pilih Pola (sidebar)
            document.querySelectorAll('#assign-form select[name="shift_id"], #assign-form select[name="pattern_id"]')
                .forEach(function(el) {
                    el.choicesInstance = new Choices(el, {
                        searchEnabled: true,
                        itemSelectText: '',
                        shouldSort: false,
                        searchPlaceholderValue: 'Cari...'
                    });
                });

            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) fetchEmployees(currentPage - 1);
            });
            nextBtn.addEventListener('click', () => {
                if (currentPage < lastPage) fetchEmployees(currentPage + 1);
            });

            document.getElementById('select-all-btn').addEventListener('click', () => {
                fetch(allIdsUrl + '?' + buildParams(1))
                    .then(r => r.json())
                    .then(json => {
                        json.ids.forEach(id => selectedMap.set(id, true));
                        updateCount();
                        fetchEmployees(currentPage);
                    });
            });

            document.getElementById('deselect-all-btn').addEventListener('click', () => {
                selectedMap.clear();
                updateCount();
                fetchEmployees(currentPage);
            });

            document.getElementById('assign-form').addEventListener('submit', function(e) {
                if (selectedMap.size === 0) {
                    e.preventDefault();
                    alert('Pilih minimal 1 karyawan sebelum menyimpan.');
                    return;
                }
                var container = document.getElementById('hidden-inputs-container');
                container.innerHTML = '';
                selectedMap.forEach((_, id) => {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = id;
                    container.appendChild(input);
                });
            });

            fetchEmployees(1);
        })();
    </script>
@endpush
