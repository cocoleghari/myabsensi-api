<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Absensi extends Model
{
    protected $fillable = [
        'employee_id',
        'pusat_lokasi_id',
        'shift_id',
        'tanggal_absen',
        'tipe_absen',
        'waktu_absen',
        'latitude',
        'longitude',
        'jarak_meter',
        'foto_absen_path',
        'confidence_score',
        'wajah_cocok',
        'status',
        'menit_terlambat',
        'menit_lembur',
        'catatan',
    ];

    protected $casts = [
        'tanggal_absen' => 'date:Y-m-d',
        'waktu_absen' => 'datetime:Y-m-d H:i:s',
        'latitude' => 'float',
        'longitude' => 'float',
        'jarak_meter' => 'float',
        'confidence_score' => 'float',
        'wajah_cocok' => 'boolean',
        'menit_terlambat' => 'integer',
        'menit_lembur' => 'integer',
    ];

    protected $appends = ['foto_absen_url'];

    public function getFotoAbsenUrlAttribute(): ?string
    {
        if (! $this->foto_absen_path) {
            return null;
        }

        // Path dengan subfolder (misal: permintaan_absen/req_xxx.jpg)
        if (str_contains($this->foto_absen_path, '/')) {
            return Storage::disk('public')->url($this->foto_absen_path);
        }

        // Path lama hanya nama file → prefix folder foto_absensi
        return Storage::disk('public')->url('foto_absensi/'.$this->foto_absen_path);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Pusat lokasi tempat karyawan absen.
     * Menggunakan PusatLokasi (bukan Lokasi lama).
     */
    public function pusatLokasi(): BelongsTo
    {
        return $this->belongsTo(PusatLokasi::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function permintaanAbsen(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PermintaanAbsen::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal_absen', now()->toDateString());
    }

    public function scopeMasuk($query)
    {
        return $query->where('tipe_absen', 'masuk');
    }

    public function scopePulang($query)
    {
        return $query->where('tipe_absen', 'pulang');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isTerlambat(): bool
    {
        return $this->status === 'terlambat';
    }

    public function isLembur(): bool
    {
        return $this->menit_lembur > 0;
    }
}
