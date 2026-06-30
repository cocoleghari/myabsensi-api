@extends('layouts.admin')
@section('title', isset($shift) ? 'Edit Shift' : 'Tambah Shift')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="flex items-center gap-2 mb-6 text-[13px] text-gray-400">
        <a href="{{ route('admin.master-shift.index') }}" class="hover:text-gray-600 transition-colors font-medium">Shift
            Kerja</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600 font-medium">{{ isset($shift) ? 'Edit' : 'Tambah' }}</span>
    </div>

    @if ($errors->any())
        <div class="mb-5 bg-rose-50 border border-rose-100 rounded-2xl px-5 py-4 flex gap-3">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <ul class="text-[13px] text-rose-600 space-y-0.5 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <form method="POST"
            action="{{ isset($shift) ? route('admin.master-shift.update', $shift->id) : route('admin.master-shift.store') }}">
            @csrf
            @if (isset($shift))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- Nama & Kode --}}
                <div class="md:col-span-2">
                    <label class="form-label">Nama Shift <span class="text-rose-400">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $shift->nama ?? '') }}" class="form-input"
                        placeholder="misal: Shift Pagi, Satpam Flex">
                    @error('nama')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Kode <span class="text-rose-400">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode', $shift->kode ?? '') }}" class="form-input"
                        placeholder="misal: PAGI, SATPAM">
                    @error('kode')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Company <span class="text-rose-400">*</span></label>
                    <select name="company_id" class="choices-select form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ old('company_id', $shift->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tipe Shift --}}
                <div class="md:col-span-3">
                    <label class="form-label">Tipe Shift <span class="text-rose-400">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-1">
                        <label
                            class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all tipe-card"
                            data-tipe="reguler"
                            style="{{ old('tipe', $shift->tipe ?? 'reguler') == 'reguler' ? 'border-color:#f97316; background:#fff7ed' : 'border-color:#E5E7EB' }}">
                            <input type="radio" name="tipe" value="reguler"
                                {{ old('tipe', $shift->tipe ?? 'reguler') == 'reguler' ? 'checked' : '' }}
                                class="mt-0.5 text-orange-500 focus:ring-orange-400 flex-shrink-0">
                            <div>
                                <p class="text-[13.5px] font-semibold text-gray-800">Reguler</p>
                                <p class="text-[12px] text-gray-400 mt-0.5">Jam masuk & pulang kaku, ada perhitungan
                                    terlambat & lembur. Untuk karyawan kantor biasa.</p>
                            </div>
                        </label>

                        <label
                            class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all tipe-card"
                            data-tipe="flex"
                            style="{{ old('tipe', $shift->tipe ?? '') == 'flex' ? 'border-color:#f97316; background:#fff7ed' : 'border-color:#E5E7EB' }}">
                            <input type="radio" name="tipe" value="flex"
                                {{ old('tipe', $shift->tipe ?? '') == 'flex' ? 'checked' : '' }}
                                class="mt-0.5 text-orange-500 focus:ring-orange-400 flex-shrink-0">
                            <div>
                                <p class="text-[13.5px] font-semibold text-gray-800">Fleksibel</p>
                                <p class="text-[12px] text-gray-400 mt-0.5">Tanpa jam kaku, hanya mencatat kehadiran. Cocok
                                    untuk satpam, IT, atau karyawan dengan jadwal tidak menentu.</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Fields reguler (disembunyikan jika flex) --}}
                <div id="reguler-fields" class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-5"
                    style="{{ old('tipe', $shift->tipe ?? 'reguler') == 'flex' ? 'opacity:0.4; pointer-events:none' : '' }}">

                    <div id="flex-hint"
                        class="{{ old('tipe', $shift->tipe ?? 'reguler') == 'flex' ? '' : 'hidden' }} md:col-span-3 mb-2">
                        <p class="text-[12px] text-amber-600 bg-amber-50 border border-amber-100 rounded-xl px-4 py-2.5">
                            Mode fleksibel aktif — field jam di bawah opsional. Jika dikosongkan, sistem akan menggunakan
                            nilai default (00:00 – 23:59).
                        </p>
                    </div>

                    <div class="md:col-span-3">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                            <p class="text-[13px] font-semibold text-gray-700">Pengaturan Jam Kerja</p>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Jam Masuk <span class="text-rose-400">*</span></label>
                        <input type="time" name="jam_masuk" value="{{ old('jam_masuk', $shift->jam_masuk ?? '08:00') }}"
                            class="form-input" id="field-jam-masuk">
                        @error('jam_masuk')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Jam Pulang <span class="text-rose-400">*</span></label>
                        <input type="time" name="jam_pulang"
                            value="{{ old('jam_pulang', $shift->jam_pulang ?? '17:00') }}" class="form-input"
                            id="field-jam-pulang">
                        @error('jam_pulang')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Batas Waktu Pulang <span class="text-rose-400">*</span>
                            <span class="text-gray-400 font-normal">(termasuk lembur)</span>
                        </label>
                        <input type="time" name="batas_waktu_pulang"
                            value="{{ old('batas_waktu_pulang', $shift->batas_waktu_pulang ?? '20:00') }}"
                            class="form-input">
                        @error('batas_waktu_pulang')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Toleransi Terlambat (menit)</label>
                        <input type="number" name="toleransi_terlambat_menit" min="0" max="120"
                            value="{{ old('toleransi_terlambat_menit', $shift->toleransi_terlambat_menit ?? 15) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Window Masuk Awal (menit)
                            <span class="text-gray-400 font-normal">(berapa menit sebelum jam masuk tombol absen
                                muncul)</span>
                        </label>
                        <input type="number" name="window_masuk_awal_menit" min="0" max="240"
                            value="{{ old('window_masuk_awal_menit', $shift->window_masuk_awal_menit ?? 60) }}"
                            class="form-input">
                    </div>

                    <div class="md:col-span-3">
                        <div class="flex items-center gap-2 mb-4 mt-2">
                            <div class="w-1 h-4 rounded-full" style="background:#f97316"></div>
                            <p class="text-[13px] font-semibold text-gray-700">Pengaturan Tambahan</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @php
                                $toggles = [
                                    [
                                        'name' => 'melewati_tengah_malam',
                                        'label' => 'Melewati Tengah Malam',
                                        'desc' => 'Untuk shift malam yang berakhir di hari berikutnya',
                                    ],
                                    [
                                        'name' => 'berlaku_hari_libur',
                                        'label' => 'Berlaku Hari Libur',
                                        'desc' => 'Shift tetap aktif di hari libur nasional',
                                    ],
                                    [
                                        'name' => 'berlaku_akhir_pekan',
                                        'label' => 'Berlaku Akhir Pekan',
                                        'desc' => 'Shift tetap aktif di Sabtu/Minggu',
                                    ],
                                ];
                            @endphp
                            @foreach ($toggles as $toggle)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div>
                                        <p class="text-[13.5px] font-medium text-gray-800">{{ $toggle['label'] }}</p>
                                        <p class="text-[12px] text-gray-400">{{ $toggle['desc'] }}</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer ml-4 flex-shrink-0">
                                        <input type="checkbox" name="{{ $toggle['name'] }}" value="1"
                                            {{ old($toggle['name'], $shift->{$toggle['name']} ?? false) ? 'checked' : '' }}
                                            class="sr-only peer">
                                        <div
                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                            peer-checked:after:translate-x-full peer-checked:after:border-white
                                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                            after:bg-white after:border-gray-300 after:border after:rounded-full
                                            after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500">
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Keterangan & Status --}}
                <div class="md:col-span-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="form-input resize-none"
                        placeholder="Opsional - catatan tambahan tentang shift ini">{{ old('keterangan', $shift->keterangan ?? '') }}</textarea>
                </div>

                <div class="md:col-span-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $shift->is_active ?? true) ? 'checked' : '' }}
                            class="w-[18px] h-[18px] rounded-md border-gray-300 text-orange-500 focus:ring-orange-400">
                        <span class="text-[13.5px] font-medium text-gray-600">Shift aktif</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-4 mt-7 pt-6 border-t border-gray-100">
                <button type="submit"
                    class="flex items-center gap-2 text-[13.5px] font-semibold px-6 py-3 rounded-xl text-white shadow-[0_4px_14px_rgba(249,115,22,0.35)] hover:opacity-90 transition-opacity"
                    style="background:linear-gradient(135deg, #f97316 0%, #ea580c 100%)">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isset($shift) ? 'Update Shift' : 'Simpan Shift' }}
                </button>
                <a href="{{ route('admin.master-shift.index') }}"
                    class="text-[13px] text-gray-400 hover:text-gray-600 transition-colors font-medium">Batal</a>
            </div>
        </form>
    </div>

    <style>
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #4B5563;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            font-size: 13.5px;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 12px 16px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.1);
        }

        .form-select {
            width: 100%;
            font-size: 13.5px;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 12px 16px;
            outline: none;
            background: #fff;
        }

        .form-error {
            font-size: 11px;
            color: #ef4444;
            margin-top: 4px;
        }

        .choices__inner {
            border: 1px solid #E5E7EB !important;
            border-radius: 12px !important;
            padding: 9px 16px !important;
            font-size: 13.5px !important;
            background: #fff !important;
            min-height: 46px;
        }

        .choices.is-focused .choices__inner {
            border-color: #fb923c !important;
        }

        .choices__list--dropdown {
            border-radius: 12px !important;
            font-size: 13.5px !important;
        }

        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #fff7ed !important;
            color: #ea580c !important;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // Choices.js
        document.querySelectorAll('.choices-select').forEach(function(el) {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
        });

        // Toggle tipe shift
        const regulerFields = document.getElementById('reguler-fields');
        const tipeCards = document.querySelectorAll('.tipe-card');

        function applyTipe(tipe) {
            const isFlex = tipe === 'flex';

            // Opacity saja untuk visual hint, tidak mengunci interaksi
            regulerFields.style.opacity = '1';
            regulerFields.style.pointerEvents = '';

            // Hanya required yang berubah
            document.getElementById('field-jam-masuk').required = !isFlex;
            document.getElementById('field-jam-pulang').required = !isFlex;

            // Highlight card terpilih
            tipeCards.forEach(function(card) {
                const isActive = card.dataset.tipe === tipe;
                card.style.borderColor = isActive ? '#f97316' : '#E5E7EB';
                card.style.background = isActive ? '#fff7ed' : '';
            });

            // Tampilkan/sembunyikan hint untuk flex
            const flexHint = document.getElementById('flex-hint');
            if (flexHint) flexHint.classList.toggle('hidden', !isFlex);
        }

        // Listener
        document.querySelectorAll('input[name="tipe"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                applyTipe(this.value);
            });
        });

        // Init saat load
        const currentTipe = document.querySelector('input[name="tipe"]:checked')?.value || 'reguler';
        applyTipe(currentTipe);
    </script>
@endpush
