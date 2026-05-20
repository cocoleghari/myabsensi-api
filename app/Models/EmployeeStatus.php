<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'color',
        'is_active',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Hanya yang tampil di dropdown (is_visible = true) */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /** Hanya status yang mengizinkan absen/cuti */
    public function scopeCanAttend($query)
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'employee_status_id');
    }
}
