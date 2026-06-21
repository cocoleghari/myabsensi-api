<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'position_id',
        'job_level_id',
        'job_grade_id',
        'employee_code',
        'nik',
        'ktp_number',
        'full_name',
        'nickname',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'marital_status',
        'religion',
        'blood_type',
        'foto_wajah_path',
        'wajah_terdaftar',
        'photo_url',
        'phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'address',
        'city',
        'province',
        'postal_code',
        'employment_type',
        'join_date',
        'contract_end_date',
        'resign_date',
        'employee_status_id',
        'npwp',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'last_education',
        'last_education_major',
        'last_education_institution',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'join_date' => 'date',
        'contract_end_date' => 'date',
        'resign_date' => 'date',
        'wajah_terdaftar' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(EmployeeStatus::class, 'employee_status_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function activeShift(): HasOne
    {
        return $this->hasOne(EmployeeShift::class)
            ->where('effective_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->latestOfMany('effective_date');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function pusatLokasis(): BelongsToMany
    {
        return $this->belongsToMany(PusatLokasi::class, 'employee_pusat_lokasi')
            ->withPivot(['radius_meter', 'keterangan'])
            ->withTimestamps();
    }

    public function employeePusatLokasis(): HasMany
    {
        return $this->hasMany(EmployeePusatLokasi::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?? $this->full_name;
    }

    public function isActive(): bool
    {
        return $this->status?->is_active ?? false;
    }

    public function isContractExpiringSoon(int $days = 30): bool
    {
        if (! $this->contract_end_date) {
            return false;
        }

        return $this->contract_end_date->diffInDays(now()) <= $days
            && $this->contract_end_date->isFuture();
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getWorkingMonthsAttribute(): int
    {
        $end = $this->resign_date ?? now();

        return (int) $this->join_date->diffInMonths($end);
    }

    public function getFotoWajahUrlAttribute(): ?string
    {
        return $this->foto_wajah_path ? Storage::disk('public')->url($this->foto_wajah_path) : null;
    }
}
