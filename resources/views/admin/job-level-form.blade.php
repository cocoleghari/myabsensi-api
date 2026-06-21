@extends('layouts.admin')

@section('title', isset($jobLevel) ? 'Edit Job Level' : 'Tambah Job Level')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div>
        <div class="flex items-center gap-2 mb-5 text-xs text-gray-400">
            <a href="{{ route('admin.job-level.index') }}" class="hover:text-gray-600 transition-colors">Job Level</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span
                class="text-gray-600 truncate">{{ isset($jobLevel) ? 'Edit — ' . $jobLevel->name : 'Tambah Job Level' }}</span>
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
            action="{{ isset($jobLevel) ? route('admin.job-level.update', $jobLevel->id) : route('admin.job-level.store') }}"
            class="bg-white border border-gray-100 rounded-2xl p-5 sm:p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
            @csrf
            @if (isset($jobLevel))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nama Level <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $jobLevel->name ?? '') }}"
                        class="form-input">
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Company <span class="text-rose-400">*</span></label>
                    <select name="company_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ old('company_id', $jobLevel->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Urutan</label>
                    <input type="number" name="order" value="{{ old('order', $jobLevel->order ?? 0) }}"
                        class="form-input">
                    @error('order')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-3 pt-1">
                    <p class="text-[12.5px] font-semibold text-gray-700">Status Aktif</p>
                    <label class="toggle-switch flex-shrink-0">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $jobLevel->is_active ?? true) ? 'checked' : '' }}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" rows="3" class="form-input resize-none">{{ old('description', $jobLevel->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-4 mt-6">
                <button type="submit"
                    class="flex items-center gap-2 text-xs font-medium px-5 py-2.5 rounded-xl text-white transition-opacity hover:opacity-90 shadow-sm"
                    style="background:#f97316">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isset($jobLevel) ? 'Update Job Level' : 'Simpan Job Level' }}
                </button>
                <a href="{{ route('admin.job-level.index') }}"
                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors">Batal</a>
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
            padding: 10px 13px;
            outline: none;
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
        document.querySelectorAll('.form-select').forEach(function(el) {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                placeholder: true
            });
        });
    </script>
@endsection
