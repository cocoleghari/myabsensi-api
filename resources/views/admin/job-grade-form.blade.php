@extends('layouts.admin')
@section('title', isset($jobGrade) ? 'Edit Job Grade' : 'Tambah Job Grade')

@section('content')
    <div class="flex items-center gap-2 mb-6 text-xs text-gray-400">
        <a href="{{ route('admin.job-grade.index') }}" class="hover:text-gray-600 transition">Job Grade</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-gray-600">{{ isset($jobGrade) ? 'Edit Job Grade' : 'Tambah Job Grade' }}</span>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                <ul class="text-xs text-red-600 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ isset($jobGrade) ? route('admin.job-grade.update', $jobGrade->id) : route('admin.job-grade.store') }}">
            @csrf
            @if (isset($jobGrade))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
                <div>
                    <label class="form-label">Nama Job Grade <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $jobGrade->name ?? '') }}" class="form-input"
                        required>
                </div>

                <div>
                    <label class="form-label">Company <span class="text-red-400">*</span></label>
                    <select name="company_id" class="form-input" required>
                        <option value="">-- Pilih Company --</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}"
                                {{ old('company_id', $jobGrade->company_id ?? '') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Kode <span class="text-red-400">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $jobGrade->code ?? '') }}" class="form-input"
                        required>
                </div>

                <div>
                    <label class="form-label">Grade (Angka Hierarki) <span class="text-red-400">*</span></label>
                    <input type="number" name="grade" value="{{ old('grade', $jobGrade->grade ?? '') }}"
                        class="form-input" required>
                    <p class="text-[10.5px] text-gray-400 mt-1">Semakin besar angka, semakin tinggi level grade.</p>
                </div>

                <div>
                    <label class="form-label">Urutan <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="number" name="order" value="{{ old('order', $jobGrade->order ?? 0) }}"
                        class="form-input">
                </div>

                <div class="flex items-end pb-2.5">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                            {{ old('is_active', $jobGrade->is_active ?? true) ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                        <label for="is_active" class="text-[12.5px] text-gray-600">Job grade aktif</label>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Deskripsi <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="description" rows="3" class="form-input">{{ old('description', $jobGrade->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-gray-100">
                <button type="submit"
                    class="flex items-center gap-2 text-xs font-medium px-5 py-2.5 rounded-lg text-white hover:opacity-90 transition"
                    style="background:#f97316">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ isset($jobGrade) ? 'Update Job Grade' : 'Simpan Job Grade' }}
                </button>
                <a href="{{ route('admin.job-grade.index') }}" class="text-xs text-gray-400 hover:text-gray-600">Batal</a>
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
            transition: border-color .15s;
            background: #fff;
        }

        .form-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .08);
        }
    </style>
@endsection
