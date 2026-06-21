@extends('layouts.admin')

@section('title', isset($department) ? 'Edit Department' : 'Tambah Department')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="flex items-center gap-2 mb-6 text-[13px] text-gray-400">
        <a href="{{ route('admin.department.index') }}"
            class="hover:text-gray-600 transition-colors font-medium">Department</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600 font-medium">{{ isset($department) ? 'Edit' : 'Tambah' }}</span>
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
            action="{{ isset($department) ? route('admin.department.update', $department->id) : route('admin.department.store') }}">
            @csrf
            @if (isset($department))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-2">
                    <label class="form-label">Nama Department <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $department->name ?? '') }}"
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" value="{{ old('code', $department->code ?? '') }}"
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Company <span class="text-rose-400">*</span></label>
                    <select name="company_id" class="choices-select form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ old('company_id', $department->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Parent Department</label>
                    <select name="parent_id" class="choices-select form-select">
                        <option value="">-- Tidak ada (Root) --</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}"
                                {{ old('parent_id', $department->parent_id ?? '') == $d->id ? 'selected' : '' }}>
                                {{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Manager Department</label>
                    <select name="manager_id" class="choices-select form-select">
                        <option value="">-- Belum ada manager --</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}"
                                {{ old('manager_id', $department->manager_id ?? '') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Urutan</label>
                    <input type="number" name="order" value="{{ old('order', $department->order ?? 0) }}" min="0"
                        class="form-input">
                </div>

                <div class="md:col-span-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" rows="3" class="form-input resize-none">{{ old('description', $department->description ?? '') }}</textarea>
                </div>

                <div class="md:col-span-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}
                            class="w-[18px] h-[18px] rounded-md border-gray-300 text-orange-500 focus:ring-orange-400">
                        <span class="text-[13.5px] font-medium text-gray-600">Department aktif</span>
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
                    {{ isset($department) ? 'Update Data' : 'Simpan Department' }}
                </button>
                <a href="{{ route('admin.department.index') }}"
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
        document.querySelectorAll('.choices-select').forEach(function(el) {
            new Choices(el, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });
        });
    </script>
@endpush
