<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'job_level_id' => ['nullable', 'integer', 'exists:job_levels,id'],
            'job_grade_id' => ['nullable', 'integer', 'exists:job_grades,id'],

            'name' => ['required', 'string', 'max:100'],

            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('positions')
                    ->where(fn ($q) => $q
                        ->where('company_id', $this->company_id)
                        ->whereNull('deleted_at')
                    ),
            ],

            'description' => ['nullable', 'string', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan tidak ditemukan.',
            'department_id.exists' => 'Departemen tidak ditemukan.',
            'job_level_id.exists' => 'Job level tidak ditemukan.',
            'job_grade_id.exists' => 'Job grade tidak ditemukan.',
            'name.required' => 'Nama posisi wajib diisi.',
            'name.max' => 'Nama posisi maksimal 100 karakter.',
            'code.max' => 'Kode posisi maksimal 20 karakter.',
            'code.unique' => 'Kode posisi sudah digunakan di perusahaan ini.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'order.integer' => 'Urutan harus berupa angka.',
            'order.min' => 'Urutan tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Kosongkan string menjadi null agar unique rule tidak konflik
        if ($this->code === '') {
            $this->merge(['code' => null]);
        }

        // Default is_active = true jika tidak dikirim
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        // Default order = 0 jika tidak dikirim
        if (! $this->has('order') || $this->order === null) {
            $this->merge(['order' => 0]);
        }
    }
}
