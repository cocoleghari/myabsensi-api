<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeShift extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_id',
        'pattern_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function pattern(): BelongsTo
    {
        return $this->belongsTo(ShiftWeeklyPattern::class, 'pattern_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Shift yang aktif pada tanggal tertentu (default: hari ini).
     *
     * Contoh: EmployeeShift::aktifPada(today())->where('employee_id', 1)->first()
     */
    public function scopeAktifPada($query, ?Carbon $tanggal = null)
    {
        $tanggal ??= now();

        return $query
            ->where('tanggal_mulai', '<=', $tanggal->toDateString())
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', $tanggal->toDateString());
            });
    }

    // -----------------------------------------------------------------------
    // Helper: dapatkan Shift aktif pada tanggal tertentu
    // Otomatis handle mode shift langsung vs pola mingguan
    // -----------------------------------------------------------------------
    public function getShiftForDate(\Carbon\Carbon $tanggal): ?Shift
    {
        // Mode pola mingguan
        if ($this->pattern_id && $this->relationLoaded('pattern')) {
            return $this->pattern->getShiftForDate($tanggal);
        }

        // Mode shift langsung (perilaku lama)
        return $this->shift;
    }
}
