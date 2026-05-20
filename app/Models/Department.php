<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'manager_id',
        'name',
        'code',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['breadcrumb'];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Departemen induk (parent).
     * Null jika ini departemen level teratas.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Sub-departemen langsung di bawah departemen ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id')->orderBy('order');
    }

    /**
     * Semua sub-departemen secara rekursif (eager loadable).
     * Contoh: Department::with('allChildren')->get()
     */
    public function allChildren(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id')
            ->orderBy('order')
            ->with('allChildren');
    }

    /**
     * Manajer departemen ini.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Semua karyawan di departemen ini.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Semua posisi yang tersedia di departemen ini.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Apakah departemen ini adalah level teratas (tidak punya parent)?
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Apakah departemen ini punya sub-departemen?
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Ambil jalur hierarki dari root ke departemen ini.
     * Misal: "Teknologi > IT > Backend"
     */
    public function getBreadcrumbAttribute(): string
    {
        $parts = collect([$this->name]);
        $current = $this;

        while ($current->parent_id) {
            $current = $current->parent;
            $parts->prepend($current->name);
        }

        return $parts->implode(' > ');
    }

    /**
     * Hitung total karyawan aktif di departemen ini (tidak termasuk sub-departemen).
     */
    public function getTotalEmployeesAttribute(): int
    {
        return $this->employees()->count();
    }
}
