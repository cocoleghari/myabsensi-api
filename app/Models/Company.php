<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'code',
        'industry',
        'logo',
        'description',
        'npwp',
        'nib',
        'established_date',
        'email',
        'phone',
        'fax',
        'website',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'timezone',
        'work_days',
        'default_clock_in',
        'default_clock_out',
        'is_active',
    ];

    protected $casts = [
        'established_date' => 'date',
        'is_active' => 'boolean',
        'default_clock_in' => 'string',
        'default_clock_out' => 'string',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Hitung total karyawan aktif.
     */
    public function getTotalActiveEmployeesAttribute(): int
    {
        return $this->employees()->whereHas('status', fn ($q) => $q->where('name', 'Aktif'))->count();
    }
}
