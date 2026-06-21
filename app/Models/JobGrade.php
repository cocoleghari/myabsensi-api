<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'grade',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'grade' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'job_grade_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Hanya grade aktif, diurutkan dari grade tertinggi.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('grade', 'desc');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Label lengkap: "X_c — Wakil Kepala Wilayah Senior"
     */
    public function getFullLabelAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }
}
