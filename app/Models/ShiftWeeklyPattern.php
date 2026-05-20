<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftWeeklyPattern extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'nama', 'kode', 'keterangan', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const HARI_LABELS = [
        0 => 'Senin', 1 => 'Selasa', 2 => 'Rabu',
        3 => 'Kamis', 4 => 'Jumat', 5 => 'Sabtu', 6 => 'Minggu',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(ShiftWeeklyPatternDay::class, 'pattern_id')
            ->orderBy('hari');
    }

    // -------------------------------------------------------------------------
    // Helper: ambil Shift yang berlaku pada tanggal tertentu
    // Return null jika hari itu libur atau tidak ada konfigurasi
    // -------------------------------------------------------------------------
    public function getShiftForDate(\Carbon\Carbon $tanggal): ?Shift
    {
        // ISO: Senin=0 ... Minggu=6
        $hari = ($tanggal->dayOfWeek + 6) % 7;

        $day = $this->days->firstWhere('hari', $hari);

        if (! $day || $day->is_libur || ! $day->shift_id) {
            return null; // hari libur / off
        }

        return $day->shift; // relasi sudah di-load via with('days.shift')
    }
}
