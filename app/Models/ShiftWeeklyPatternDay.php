<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftWeeklyPatternDay extends Model
{
    protected $fillable = [
        'pattern_id', 'shift_id', 'hari', 'is_libur', 'keterangan',
    ];

    protected $casts = [
        'is_libur' => 'boolean',
        'hari' => 'integer',
    ];

    public function pattern(): BelongsTo
    {
        return $this->belongsTo(ShiftWeeklyPattern::class, 'pattern_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function getHariLabelAttribute(): string
    {
        return ShiftWeeklyPattern::HARI_LABELS[$this->hari] ?? "Hari {$this->hari}";
    }
}
