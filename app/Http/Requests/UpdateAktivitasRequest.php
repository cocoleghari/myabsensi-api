<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAktivitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tugas' => 'sometimes|required|string|max:255',
            'mulai' => 'sometimes|required|date',
            'berakhir' => 'sometimes|required|date|after_or_equal:mulai',
            'tipe_aktivitas' => 'sometimes|required|string|in:Kunjungan,Rapat,Survey,Pengiriman,Lainnya',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'akurasi_meter' => 'nullable|numeric|min:0',
            'fotos' => 'nullable|array|max:5',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            'hapus_foto_ids' => 'nullable|array',
            'hapus_foto_ids.*' => 'integer|exists:aktivitas_foto,id',
        ];
    }
}
