@extends('layouts.admin')

@section('title', 'Assign Karyawan')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="flex items-center gap-2 mb-5 text-xs text-gray-400">
        <a href="{{ route('admin.pengaturan-lokasi.index') }}" class="hover:text-gray-600 transition-colors">Pengaturan
            Lokasi</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600 truncate">Assign Karyawan — {{ $lokasi->nama_lokasi }}</span>
    </div>

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

    <form method="POST" action="{{ route('admin.pengaturan-lokasi.assign.store', $lokasi->id) }}" id="assign-form">
        @csrf
        <div id="hidden-inputs-container"></div>

        <div class="flex flex-col lg:flex-row gap-5">
            {{-- Sidebar config --}}
            <div class="lg:w-[300px] flex-shrink-0">
                <div class="lg:sticky lg:top-24 space-y-4">

                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                            <p class="text-xs font-semibold text-gray-700">Lokasi</p>
                        </div>
                        <p class="text-[14px] font-bold text-gray-800">{{ $lokasi->nama_lokasi }}</p>
                        <p class="text-[12px] text-gray-400 mt-1">{{ $lokasi->titik_kordinat }}</p>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                            <p class="text-xs font-semibold text-gray-700">Konfigurasi</p>
                        </div>

                        <label class="form-label">Radius (meter)</label>
                        <input type="number" name="radius_meter" value="{{ old('radius_meter', 100) }}" min="10"
                            max="50000" class="form-input mb-3">

                        <label class="form-label">Keterangan (opsional)</label>
                        <input type="text" name="keterangan" value="{{ old('keterangan') }}" class="form-input mb-3">

                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="overwrite" value="1" {{ old('overwrite') ? 'checked' : '' }}
                                class="w-4 h-4 mt-0.5 rounded border-gray-300 text-orange-500 focus:ring-orange-400 flex-shrink-0">
                            <span class="text-[11.5px] font-medium text-gray-600 leading-relaxed">
                                Timpa assignment lama (hapus karyawan yang sudah ter-assign, ganti dengan pilihan baru)
                            </span>
                        </label>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
                        <p class="text-[12px] text-gray-400 mb-1">Karyawan Terpilih</p>
                        <p class="text-[28px] font-bold leading-none" style="color:#f97316" id="selected-count">0</p>
                    </div>

                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 text-[13px] font-semibold px-5 py-3 rounded-xl text-white transition-opacity hover:opacity-90 shadow-sm"
                        style="background:#f97316">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Assignment
                    </button>
                    <a href="{{ route('admin.pengaturan-lokasi.index') }}"
                        class="block text-center text-[12.5px] text-gray-400 hover:text-gray-600 transition-colors">Batal</a>
                </div>
            </div>

            {{-- Main list --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-[0_2px_8px_rgba(16,24,40,0.05)]">

                    {{-- Search bar --}}
                    <div class="p-5 border-b border-gray-100 space-y-3">
                        <div class="relative">
                            <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" id="employee-search" placeholder="Cari nama karyawan..."
                                class="w-full pl-10 pr-4 py-2.5 text-[13px] border border-gray-200 rounded-xl focus:outline-none focus:border-orange-400 transition-all">
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <select id="department-filter" multiple
                                class="filter-select-assign text-[13px] border border-gray-200 rounded-xl px-3 py-2.5">
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>

                            <select id="position-filter" multiple
                                class="filter-select-assign text-[13px] border border-gray-200 rounded-xl px-3 py-2.5">
                                @foreach ($positions as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>

                            <select id="job-level-filter" multiple
                                class="filter-select-assign text-[13px] border border-gray-200 rounded-xl px-3 py-2.5">
                                @foreach ($jobLevels as $jl)
                                    <option value="{{ $jl->id }}">{{ $jl->name }}</option>
                                @endforeach
                            </select>

                            <select id="job-grade-filter" multiple
                                class="filter-select-assign text-[13px] border border-gray-200 rounded-xl px-3 py-2.5">
                                @foreach ($jobGrades as $jg)
                                    <option value="{{ $jg->id }}">{{ $jg->name }} ({{ $jg->code }})
                                    </option>
                                @endforeach
                            </select>

                            <div class="flex items-center gap-2 text-[12px] ml-auto flex-shrink-0">
                                <button type="button" id="select-all-filtered-btn"
                                    class="text-blue-600 hover:underline font-medium whitespace-nowrap">Pilih Semua Hasil
                                    Ini</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" id="deselect-all-btn"
                                    class="text-gray-500 hover:underline font-medium whitespace-nowrap">Hapus Semua
                                    Pilihan</button>
                            </div>
                        </div>
                    </div>

                    {{-- List --}}
                    <div id="employee-list" class="divide-y divide-gray-50 min-h-[300px]">
                        <p class="px-5 py-12 text-center text-[13px] text-gray-400">Memuat data karyawan...</p>
                    </div>

                    {{-- Pagination --}}
                    <div class="p-4 border-t border-gray-100 flex items-center justify-between gap-3">
                        <p class="text-[12px] text-gray-400" id="pagination-info">-</p>
                        <div class="flex items-center gap-2">
                            <button type="button" id="prev-page-btn"
                                class="px-3.5 py-2 rounded-lg text-[12px] font-semibold border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                ← Sebelumnya
                            </button>
                            <button type="button" id="next-page-btn"
                                class="px-3.5 py-2 rounded-lg text-[12px] font-semibold border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                                Selanjutnya →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style>
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            font-size: 12px;
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

        .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 10px !important;
            padding: 8px 13px !important;
            font-size: 13px !important;
            background: #fff !important;
            min-height: 41px;
        }

        .choices.is-focused .choices__inner {
            border-color: #f97316 !important;
        }

        .choices__list--dropdown {
            border-radius: 10px !important;
            font-size: 13px !important;
        }

        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }

        .choices {
            min-width: 160px;
        }

        .choices__list--multiple .choices__item {
            background: #fff7ed !important;
            border: 1px solid #fed7aa !important;
            color: #ea580c !important;
            font-size: 11.5px !important;
            border-radius: 6px !important;
        }

        .choices__list--multiple .choices__item .choices__button {
            border-left: 1px solid #fed7aa !important;
        }
    </style>

    <script>
        (function() {
            var lokasiId = {{ $lokasi->id }};
            var searchUrl = "{{ route('admin.pengaturan-lokasi.assign.search', $lokasi->id) }}";
            var allIdsUrl = "{{ route('admin.pengaturan-lokasi.assign.all-ids', $lokasi->id) }}";
            var originalAssignedIds = new Set(@json($assignedIds));

            var selectedMap = new Map();
            originalAssignedIds.forEach(function(id) {
                selectedMap.set(id, true);
            });

            var currentPage = 1;
            var lastPage = 1;
            var filters = {
                search: '',
                department_id: [],
                position_id: [],
                job_level_id: [],
                job_grade_id: []
            };
            var debounceTimer = null;

            var listEl = document.getElementById('employee-list');
            var paginationInfo = document.getElementById('pagination-info');
            var prevBtn = document.getElementById('prev-page-btn');
            var nextBtn = document.getElementById('next-page-btn');
            var selectedCountEl = document.getElementById('selected-count');

            function updateSelectedCount() {
                selectedCountEl.textContent = selectedMap.size;
            }

            function renderRow(emp) {
                var checked = selectedMap.get(emp.id) ? 'checked' : '';
                var badge = originalAssignedIds.has(emp.id) ?
                    '<span class="text-[10.5px] px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold flex-shrink-0">Sudah Terdaftar</span>' :
                    '';

                var row = document.createElement('label');
                row.className =
                    'flex items-center gap-3 px-5 py-3 hover:bg-gray-50/60 cursor-pointer transition-colors';
                row.innerHTML =
                    '<input type="checkbox" data-id="' + emp.id + '" ' + checked +
                    ' class="employee-checkbox w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">' +
                    '<div class="flex-1 min-w-0">' +
                    '<p class="text-[13.5px] font-medium text-gray-800">' + emp.full_name + '</p>' +
                    '<p class="text-[12px] text-gray-400">' + emp.position_name + ' · ' + emp.department_name + '</p>' +
                    '</div>' + badge;

                row.querySelector('.employee-checkbox').addEventListener('change', function(e) {
                    if (e.target.checked) {
                        selectedMap.set(emp.id, true);
                    } else {
                        selectedMap.delete(emp.id);
                    }
                    updateSelectedCount();
                });

                return row;
            }

            function buildParams(page) {
                var params = new URLSearchParams();
                params.set('page', page);
                if (filters.search) params.set('search', filters.search);
                ['department_id', 'position_id', 'job_level_id', 'job_grade_id'].forEach(function(key) {
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

                fetch(searchUrl + '?' + buildParams(page).toString())
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        listEl.innerHTML = '';
                        if (json.data.length === 0) {
                            listEl.innerHTML =
                                '<p class="px-5 py-12 text-center text-[13px] text-gray-400">Tidak ada karyawan ditemukan</p>';
                        } else {
                            json.data.forEach(function(emp) {
                                listEl.appendChild(renderRow(emp));
                            });
                        }

                        lastPage = json.last_page;
                        paginationInfo.textContent = 'Halaman ' + json.current_page + ' dari ' + json.last_page +
                            ' (' + json.total + ' karyawan)';
                        prevBtn.disabled = json.current_page <= 1;
                        nextBtn.disabled = json.current_page >= json.last_page;
                    });
            }

            document.getElementById('employee-search').addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    filters.search = e.target.value;
                    fetchEmployees(1);
                }, 350);
            });

            var filterMap = {
                'department-filter': 'department_id',
                'position-filter': 'position_id',
                'job-level-filter': 'job_level_id',
                'job-grade-filter': 'job_grade_id',
            };
            var placeholderMap = {
                'department-filter': 'Semua Departemen',
                'position-filter': 'Semua Posisi',
                'job-level-filter': 'Semua Job Level',
                'job-grade-filter': 'Semua Job Grade',
            };

            Object.keys(filterMap).forEach(function(elId) {
                var el = document.getElementById(elId);
                new Choices(el, {
                    removeItemButton: true,
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: placeholderMap[elId],
                    searchPlaceholderValue: 'Cari...',
                });
                el.addEventListener('change', function() {
                    filters[filterMap[elId]] = Array.from(el.selectedOptions).map(function(o) {
                        return o.value;
                    });
                    fetchEmployees(1);
                });
            });

            prevBtn.addEventListener('click', function() {
                if (currentPage > 1) fetchEmployees(currentPage - 1);
            });
            nextBtn.addEventListener('click', function() {
                if (currentPage < lastPage) fetchEmployees(currentPage + 1);
            });

            document.getElementById('select-all-filtered-btn').addEventListener('click', function() {
                fetch(allIdsUrl + '?' + buildParams(1).toString())
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        json.ids.forEach(function(id) {
                            selectedMap.set(id, true);
                        });
                        updateSelectedCount();
                        fetchEmployees(currentPage);
                    });
            });

            document.getElementById('deselect-all-btn').addEventListener('click', function() {
                selectedMap.clear();
                updateSelectedCount();
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
                selectedMap.forEach(function(_, id) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'employee_ids[]';
                    input.value = id;
                    container.appendChild(input);
                });
            });

            updateSelectedCount();
            fetchEmployees(1);
        })();
    </script>
@endsection
