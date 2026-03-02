<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PusatLokasi extends Model
{
    use HasFactory;

    protected $table = 'pusat_lokasis';

    protected $fillable = [
        'nama_lokasi',
        'titik_kordinat',
        'keterangan',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mendapatkan array koordinat [lat, lng]
     */
    public function getKoordinatArray(): array
    {
        $parts = explode(',', $this->titik_kordinat);
        if (count($parts) == 2) {
            return [
                'lat' => trim($parts[0]),
                'lng' => trim($parts[1]),
            ];
        }

        return ['lat' => '0', 'lng' => '0'];
    }

    /**
     * Scope untuk pencarian
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('nama_lokasi', 'like', "%{$search}%")
                ->orWhere('keterangan', 'like', "%{$search}%");
        }

        return $query;
    }
}
