<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PusatLokasi extends Model
{
    use HasFactory;

    protected $table = 'pusat_lokasis';

    protected $fillable = [
        'company_id',
        'nama_lokasi',
        'titik_kordinat',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Karyawan yang terdaftar di pusat lokasi ini (via pivot).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_pusat_lokasi')
            ->withPivot(['radius_meter', 'keterangan'])
            ->withTimestamps();
    }

    /**
     * Entri pivot langsung (jika perlu akses kolom pivot secara eksplisit).
     */
    public function employeePusatLokasis(): HasMany
    {
        return $this->hasMany(EmployeePusatLokasi::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse titik_kordinat menjadi array ['lat' => ..., 'lng' => ...].
     */
    public function getKoordinatArray(): array
    {
        $parts = explode(',', $this->titik_kordinat);
        if (count($parts) === 2) {
            return [
                'lat' => (float) trim($parts[0]),
                'lng' => (float) trim($parts[1]),
            ];
        }

        return ['lat' => 0.0, 'lng' => 0.0];
    }

    /**
     * Latitude sebagai float.
     */
    public function getLatitudeAttribute(): float
    {
        return $this->getKoordinatArray()['lat'];
    }

    /**
     * Longitude sebagai float.
     */
    public function getLongitudeAttribute(): float
    {
        return $this->getKoordinatArray()['lng'];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('nama_lokasi', 'like', "%{$search}%")
                ->orWhere('keterangan', 'like', "%{$search}%");
        }

        return $query;
    }
}
