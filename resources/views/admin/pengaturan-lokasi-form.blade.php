@extends('layouts.admin')

@section('title', isset($lokasi) ? 'Edit Lokasi' : 'Tambah Lokasi')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div>
        <div class="flex items-center gap-2 mb-5 text-xs text-gray-400">
            <a href="{{ route('admin.pengaturan-lokasi.index') }}" class="hover:text-gray-600 transition-colors">Pengaturan
                Lokasi</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span
                class="text-gray-600 truncate">{{ isset($lokasi) ? 'Edit — ' . $lokasi->nama_lokasi : 'Tambah Lokasi' }}</span>
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

        <form method="POST"
            action="{{ isset($lokasi) ? route('admin.pengaturan-lokasi.update', $lokasi->id) : route('admin.pengaturan-lokasi.store') }}"
            class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            @csrf
            @if (isset($lokasi))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="form-label">Nama Lokasi <span class="text-rose-400">*</span></label>
                    <input type="text" name="nama_lokasi" value="{{ old('nama_lokasi', $lokasi->nama_lokasi ?? '') }}"
                        class="form-input">
                    @error('nama_lokasi')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">Company <span class="text-rose-400">*</span></label>
                    <select name="company_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ old('company_id', $lokasi->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Latitude <span class="text-rose-400">*</span></label>
                    <input type="text" name="latitude" id="latitude-input"
                        value="{{ old('latitude', $latitude ?? '') }}" placeholder="-7.797068" class="form-input">
                    @error('latitude')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Longitude <span class="text-rose-400">*</span></label>
                    <input type="text" name="longitude" id="longitude-input"
                        value="{{ old('longitude', $longitude ?? '') }}" placeholder="110.370529" class="form-input">
                    @error('longitude')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="button" onclick="getCurrentLocation()" id="geo-btn"
                        class="text-[12px] font-semibold text-blue-600 hover:underline flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Gunakan lokasi saat ini
                    </button>
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="form-input resize-none">{{ old('keterangan', $lokasi->keterangan ?? '') }}</textarea>
                </div>

                <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-1">
                    <div>
                        <p class="text-[12.5px] font-semibold text-gray-700">Lokasi Aktif</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Lokasi nonaktif tidak akan tersedia untuk absensi
                            karyawan</p>
                    </div>
                    <label class="toggle-switch flex-shrink-0">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $lokasi->is_active ?? true) ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-4 mt-6">
                <button type="submit"
                    class="flex items-center gap-2 text-xs font-medium px-5 py-2.5 rounded-xl text-white transition-opacity hover:opacity-90 shadow-sm"
                    style="background:#f97316">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isset($lokasi) ? 'Update Data' : 'Simpan Lokasi' }}
                </button>
                <a href="{{ route('admin.pengaturan-lokasi.index') }}"
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
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }

        .form-select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08);
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
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.08) !important;
        }

        .choices__list--dropdown {
            border-radius: 10px !important;
            border-color: #E5E7EB !important;
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

        .toggle-switch input:focus-visible+.toggle-slider {
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.25);
        }
    </style>

    <script>
        document.querySelectorAll('.form-select').forEach(function(el) {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true
            });
        });

        function getCurrentLocation() {
            var btn = document.getElementById('geo-btn');
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung geolocation.');
                return;
            }
            btn.textContent = 'Mengambil lokasi...';
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('latitude-input').value = pos.coords.latitude;
                document.getElementById('longitude-input').value = pos.coords.longitude;
                btn.textContent = 'Lokasi berhasil diambil ✓';
            }, function() {
                btn.textContent = 'Gagal mengambil lokasi, coba lagi';
            });
        }
    </script>
@endsection
