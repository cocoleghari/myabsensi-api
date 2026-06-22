@extends('layouts.admin')

@section('title', isset($pattern) ? 'Edit Pola Shift' : 'Tambah Pola Shift')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div>
        <div class="flex items-center gap-2 mb-5 text-xs text-gray-400">
            <a href="{{ route('admin.pola-shift.index') }}" class="hover:text-gray-600 transition-colors">Pola Shift
                Mingguan</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-600 truncate">{{ isset($pattern) ? 'Edit — ' . $pattern->nama : 'Tambah Pola' }}</span>
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

        @if (session('error'))
            <div class="mb-5 bg-rose-50 border border-rose-100 rounded-2xl px-4 py-3.5 flex gap-3">
                <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-rose-600">{{ session('error') }}</p>
            </div>
        @endif

        <form method="POST"
            action="{{ isset($pattern) ? route('admin.pola-shift.update', $pattern->id) : route('admin.pola-shift.store') }}">
            @csrf
            @if (isset($pattern))
                @method('PUT')
            @endif

            {{-- Header --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)] mb-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2">
                        <label class="form-label">Nama Pola <span class="text-rose-400">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama', $pattern->nama ?? '') }}"
                            class="form-input">
                        @error('nama')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Kode <span class="text-rose-400">*</span></label>
                        <input type="text" name="kode" value="{{ old('kode', $pattern->kode ?? '') }}"
                            class="form-input">
                        @error('kode')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Company <span class="text-rose-400">*</span></label>
                        <select name="company_id" id="company-select" class="form-select">
                            <option value="">-- Pilih --</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}"
                                    {{ old('company_id', $pattern->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" value="{{ old('keterangan', $pattern->keterangan ?? '') }}"
                            class="form-input">
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-1">
                        <p class="text-[12.5px] font-semibold text-gray-700">Pola Aktif</p>
                        <label class="toggle-switch flex-shrink-0">
                            <input type="checkbox" name="is_active" value="1"
                                {{ old('is_active', $pattern->is_active ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Day grid --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-[0_1px_2px_rgba(16,24,40,0.04)] overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                    <p class="text-xs font-semibold text-gray-700">Konfigurasi 7 Hari</p>
                </div>

                <div class="divide-y divide-gray-50">
                    @foreach ($hariLabels as $hari => $label)
                        @php
                            $dayData = isset($daysData) ? $daysData->firstWhere('hari', $hari) : null;
                            $isLibur = old("days.{$hari}.is_libur", $dayData['is_libur'] ?? false);
                            $shiftId = old("days.{$hari}.shift_id", $dayData['shift_id'] ?? '');
                        @endphp
                        <div class="p-5 flex flex-wrap items-center gap-4">
                            <div class="w-24 flex-shrink-0">
                                <p class="text-[13px] font-semibold text-gray-800">{{ $label }}</p>
                            </div>

                            <div class="flex-1 min-w-[200px] day-shift-wrapper" data-hari="{{ $hari }}">
                                <select name="days[{{ $hari }}][shift_id]" class="day-shift-select form-select"
                                    {{ $isLibur ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Shift --</option>
                                    @foreach ($shifts as $s)
                                        <option value="{{ $s->id }}" data-company="{{ $s->company_id }}"
                                            {{ $shiftId == $s->id ? 'selected' : '' }}>
                                            {{ $s->nama }}
                                            ({{ substr($s->jam_masuk, 0, 5) }}-{{ substr($s->jam_pulang, 0, 5) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="text" name="days[{{ $hari }}][keterangan]"
                                value="{{ old("days.{$hari}.keterangan", $dayData['keterangan'] ?? '') }}"
                                placeholder="Keterangan (opsional)" class="form-input flex-1 min-w-[160px]">

                            <label class="flex items-center gap-2 flex-shrink-0 cursor-pointer">
                                <input type="checkbox" name="days[{{ $hari }}][is_libur]" value="1"
                                    class="day-libur-checkbox w-4 h-4 rounded border-gray-300 text-rose-500 focus:ring-rose-400"
                                    data-hari="{{ $hari }}" {{ $isLibur ? 'checked' : '' }}>
                                <span class="text-[12.5px] font-medium text-gray-600">Libur</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4 mt-6">
                <button type="submit"
                    class="flex items-center gap-2 text-xs font-medium px-5 py-2.5 rounded-xl text-white transition-opacity hover:opacity-90 shadow-sm"
                    style="background:#f97316">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isset($pattern) ? 'Update Pola' : 'Simpan Pola' }}
                </button>
                <a href="{{ route('admin.pola-shift.index') }}"
                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Batal</a>
            </div>
        </form>
    </div>

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
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
        }

        .form-select {
            width: 100%;
            font-size: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 8px 11px;
            outline: none;
            background: #fff;
        }

        .form-select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
        }

        .form-select:disabled {
            background: #F3F4F6;
            color: #9CA3AF;
        }

        .form-error {
            font-size: 10px;
            color: #ef4444;
            margin-top: 4px;
        }

        .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 10px !important;
            padding: 8px 13px !important;
            font-size: 12px !important;
            background: #fff !important;
            min-height: 41px;
        }

        .choices.is-focused .choices__inner {
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

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 25px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #E5E7EB;
            border-radius: 25px;
            transition: background-color .2s ease;
        }

        .toggle-slider::before {
            position: absolute;
            content: "";
            height: 19px;
            width: 19px;
            left: 3px;
            bottom: 3px;
            background-color: #fff;
            border-radius: 50%;
            transition: transform .2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .toggle-switch input:checked+.toggle-slider {
            background-color: #f97316;
        }

        .toggle-switch input:checked+.toggle-slider::before {
            transform: translateX(19px);
        }
    </style>

    <script>
        new Choices('#company-select', {
            searchEnabled: true,
            itemSelectText: '',
            shouldSort: false,
            placeholder: true
        });

        function filterShiftsByCompany() {
            var companyId = document.getElementById('company-select').value;
            document.querySelectorAll('.day-shift-select').forEach(function(select) {
                Array.from(select.options).forEach(function(opt) {
                    if (!opt.value) return;
                    var match = !companyId || opt.dataset.company === companyId;
                    opt.hidden = !match;
                    if (!match && opt.selected) {
                        select.value = '';
                    }
                });
            });
        }

        document.getElementById('company-select').addEventListener('change', filterShiftsByCompany);
        filterShiftsByCompany();

        document.querySelectorAll('.day-libur-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function(e) {
                var hari = e.target.dataset.hari;
                var select = document.querySelector('.day-shift-wrapper[data-hari="' + hari +
                    '"] .day-shift-select');
                select.disabled = e.target.checked;
                if (e.target.checked) {
                    select.value = '';
                }
            });
        });
    </script>
@endsection
