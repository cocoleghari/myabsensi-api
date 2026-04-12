<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AktivitasFoto extends Model
{
    use HasFactory;

    protected $table = 'aktivitas_foto';

    protected $fillable = [
        'aktivitas_id',
        'foto_path',
        'urutan',
    ];

    // Append URL lengkap otomatis
    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): string
    {
        return Storage::url($this->foto_path);
    }

    public function aktivitas()
    {
        return $this->belongsTo(Aktivitas::class);
    }
}
