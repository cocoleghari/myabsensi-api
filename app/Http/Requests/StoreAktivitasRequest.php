<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAktivitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tugas' => 'required|string|max:255',
            'mulai' => 'required|date',
            'berakhir' => 'required|date|after_or_equal:mulai',
            'tipe_aktivitas_id' => 'required|exists:tipe_aktivitas,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'akurasi_meter' => 'nullable|numeric|min:0',
            'fotos' => 'nullable|array|max:5|min:1',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'tugas.required' => 'Tugas tidak boleh kosong.',
            'mulai.required' => 'Waktu mulai harus diisi.',
            'berakhir.required' => 'Waktu berakhir harus diisi.',
            'berakhir.after_or_equal' => 'Waktu berakhir tidak boleh sebelum waktu mulai.',
            'tipe_aktivitas_id.required' => 'Tipe aktivitas harus dipilih.',
            'tipe_aktivitas_id.exists' => 'Tipe aktivitas tidak valid.',
            'latitude.required' => 'Lokasi latitude diperlukan.',
            'longitude.required' => 'Lokasi longitude diperlukan.',
            'fotos.required' => 'Foto wajib diisi minimal 1.',
            'fotos.min' => 'Foto wajib diisi minimal 1.',
            'fotos.max' => 'Maksimal 5 foto.',
            'fotos.*.image' => 'File harus berupa gambar.',
            'fotos.*.mimes' => 'Format foto harus jpg, jpeg, atau png.',
            'fotos.*.max' => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
