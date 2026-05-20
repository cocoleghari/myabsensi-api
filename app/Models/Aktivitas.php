<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aktivitas extends Model
{
    use HasFactory;

    protected $table = 'aktivitas';

    protected $fillable = [
        'employee_id',
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

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /**
     * Aktivitas dimiliki oleh Employee (bukan User langsung).
     * Foreign key di migrasi adalah employee_id → constrained('users'),
     * namun secara domain sebaiknya mengacu ke Employee.
     * Sesuaikan jika migrasi diubah ke constrained('employees').
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(AktivitasFoto::class)->orderBy('urutan');
    }

    public function tipeAktivitas(): BelongsTo
    {
        return $this->belongsTo(TipeAktivitas::class, 'tipe_aktivitas_id');
    }
}
