<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aktivitas extends Model
{
    use HasFactory;

    protected $table = 'aktivitas';

    protected $fillable = [
        'user_id',
        'tugas',
        'mulai',
        'berakhir',
        'tipe_aktivitas_id',
        'tujuan',
        'kendaraan_nopol',
        'latitude',
        'longitude',
        'akurasi_meter',
    ];

    protected $casts = [
        'mulai' => 'datetime',
        'berakhir' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'akurasi_meter' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fotos()
    {
        return $this->hasMany(AktivitasFoto::class)->orderBy('urutan');
    }

    // Tambahkan relasi
    public function tipeAktivitas()
    {
        return $this->belongsTo(TipeAktivitas::class, 'tipe_aktivitas_id');
    }
}
