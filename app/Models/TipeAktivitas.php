<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipeAktivitas extends Model
{
    protected $table = 'tipe_aktivitas';

    protected $fillable = ['nama', 'has_tujuan', 'has_kendaraan'];

    protected $casts = [
        'has_tujuan' => 'boolean',
        'has_kendaraan' => 'boolean',
    ];

    public function aktivitas()
    {
        return $this->hasMany(Aktivitas::class);
    }
}
