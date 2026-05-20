<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model pivot many-to-many antara Employee dan PusatLokasi.
 *
 * Menggantikan model Lokasi (tabel lokasis) yang lama.
 * Setiap baris mewakili "karyawan X boleh absen di lokasi Y dengan radius Z meter".
 */
class EmployeePusatLokasi extends Model
{
    protected $table = 'employee_pusat_lokasi';

    protected $fillable = [
        'employee_id',
        'pusat_lokasi_id',
        'radius_meter',
        'keterangan',
    ];

    protected $casts = [
        'radius_meter' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function pusatLokasi(): BelongsTo
    {
        return $this->belongsTo(PusatLokasi::class);
    }
}
