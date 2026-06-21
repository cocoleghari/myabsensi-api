@extends('layouts.admin')
@section('title', 'Edit Assignment Shift')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="flex items-center gap-2 mb-6 text-xs text-gray-400">
        <a href="{{ route('admin.assign-shift.index') }}" class="hover:text-gray-600 transition">Assign Shift</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600">Edit Assignment</span>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">

        {{-- Info Karyawan --}}
        <div class="flex items-center gap-3 mb-6 pb-5 border-b border-gray-100">
            <div
                class="w-11 h-11 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-semibold text-sm flex-shrink-0">
                {{ strtoupper(substr($assignShift->employee?->full_name ?? '?', 0, 2)) }}
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800">{{ $assignShift->employee?->full_name ?? '-' }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $assignShift->employee?->position?->name ?? '-' }} ·
                    {{ $assignShift->employee?->department?->name ?? '-' }}
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                <ul class="text-xs text-red-600 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.assign-shift.update', $assignShift->id) }}">
            @csrf @method('PUT')

            {{-- Tab Switcher --}}
            <div class="grid grid-cols-2 border border-gray-200 rounded-xl overflow-hidden mb-6">
                <button type="button" id="tab-shift"
                    class="tab-btn flex items-center justify-center gap-2 py-2.5 text-[13px] font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Shift Kerja
                </button>
                <button type="button" id="tab-pattern"
                    class="tab-btn flex items-center justify-center gap-2 py-2.5 text-[13px] font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Pola Mingguan
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                {{-- Panel Shift --}}
                <div id="panel-shift" class="md:col-span-2">
                    <label class="form-label">Shift Kerja</label>
                    <select name="shift_id" class="choices-select form-input">
                        <option value="">-- Pilih Shift --</option>
                        @foreach ($shifts as $s)
                            <option value="{{ $s->id }}"
                                {{ old('shift_id', $assignShift->shift_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->nama }} ({{ $s->kode }}) · {{ $s->jam_masuk }} – {{ $s->jam_pulang }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Panel Pola --}}
                <div id="panel-pattern" class="md:col-span-2" style="display:none;">
                    <label class="form-label">Pola Shift Mingguan</label>
                    <select name="pattern_id" class="choices-select form-input">
                        <option value="">-- Pilih Pola --</option>
                        @foreach ($patterns as $p)
                            <option value="{{ $p->id }}"
                                {{ old('pattern_id', $assignShift->pattern_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->nama }} ({{ $p->kode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Tanggal Mulai <span class="text-red-400">*</span></label>
                    <input type="date" name="tanggal_mulai"
                        value="{{ old('tanggal_mulai', $assignShift->tanggal_mulai?->format('Y-m-d')) }}"
                        class="form-input">
                    @error('tanggal_mulai')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Tanggal Selesai <span
                            class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="date" name="tanggal_selesai"
                        value="{{ old('tanggal_selesai', $assignShift->tanggal_selesai?->format('Y-m-d')) }}"
                        class="form-input">
                    @error('tanggal_selesai')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan', $assignShift->keterangan) }}"
                        class="form-input">
                </div>
            </div>

            <div class="flex items-center gap-3 mt-7 pt-5 border-t border-gray-100">
                <button type="submit"
                    class="flex items-center gap-2 text-xs font-medium px-5 py-2.5 rounded-lg text-white hover:opacity-90 transition"
                    style="background:#f97316">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Update Assignment
                </button>
                <a href="{{ route('admin.assign-shift.index') }}"
                    class="text-xs text-gray-400 hover:text-gray-600">Batal</a>
            </div>
        </form>
    </div>

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
            transition: border-color .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #f97316;
        }

        .form-error {
            font-size: 10px;
            color: #ef4444;
            margin-top: 4px;
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

        .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 10px !important;
            padding: 8px 13px !important;
            font-size: 12px !important;
            background: #fff !important;
            min-height: 42px;
        }

        .is-focused .choices__inner {
            border-color: #f97316 !important;
        }

        .choices__list--dropdown {
            border-radius: 10px !important;
            font-size: 12px !important;
        }

        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }
    </style>

    <script>
        (function() {
            var tabShift = document.getElementById('tab-shift');
            var tabPattern = document.getElementById('tab-pattern');
            var panelShift = document.getElementById('panel-shift');
            var panelPattern = document.getElementById('panel-pattern');
            var shiftSelect = panelShift.querySelector('select[name="shift_id"]');
            var patternSelect = panelPattern.querySelector('select[name="pattern_id"]');

            // Tentukan tab aktif awal berdasarkan data yang sudah tersimpan
            var initialTab = patternSelect.value ? 'pattern' : 'shift';

            function activate(tab) {
                var isShift = tab === 'shift';
                panelShift.style.display = isShift ? '' : 'none';
                panelPattern.style.display = isShift ? 'none' : '';
                tabShift.classList.toggle('active', isShift);
                tabPattern.classList.toggle('active', !isShift);

                // Kosongkan select yang tidak aktif agar tidak terkirim keduanya
                if (isShift) {
                    patternSelect.value = '';
                    if (patternSelect.choicesInstance) patternSelect.choicesInstance.setChoiceByValue('');
                } else {
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

            // Init Choices.js
            document.querySelectorAll('.choices-select').forEach(function(el) {
                var instance = new Choices(el, {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    searchPlaceholderValue: 'Cari...'
                });
                el.choicesInstance = instance;
            });

            activate(initialTab);
        })();
    </script>
@endsection
