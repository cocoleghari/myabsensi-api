<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanAbsen extends Model
{
    protected $fillable = [
        'employee_id',
        'pusat_lokasi_id',
        'tipe_absen',
        'waktu_pengajuan',
        'latitude',
        'longitude',
        'jarak_meter',
        'alasan',
        'keterangan',
        'foto_path',
        'status',
        'approver_employee_id',
        'diproses_oleh',
        'diproses_pada',
        'catatan_admin',
        'absensi_id',
        'alamat_pengajuan',
        'jenis',
        'waktu_koreksi',
    ];

    protected $casts = [
        'waktu_pengajuan' => 'datetime',
        'diproses_pada' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'jarak_meter' => 'float',
        'waktu_koreksi' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function pusatLokasi(): BelongsTo
    {
        return $this->belongsTo(PusatLokasi::class);
    }

    /**
     * Manager yang wajib approve permintaan ini.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }

    public function diprosesoleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Resolve siapa manager yang harus approve.
     * Prioritas: manager department → fallback admin
     */
    public static function resolveApprover(Employee $employee): ?Employee
    {
        // Prioritas 1: manager department karyawan
        if ($employee->department?->manager_id) {
            $manager = Employee::find($employee->department->manager_id);
            // Jangan assign ke diri sendiri jika karyawan adalah manager-nya
            if ($manager && $manager->id !== $employee->id) {
                return $manager;
            }
        }

        // Prioritas 2: manager department parent (naik satu level)
        if ($employee->department?->parent?->manager_id) {
            return Employee::find($employee->department->parent->manager_id);
        }

        // Prioritas 3: tidak ada manager → null (admin yang handle manual)
        return null;
    }
}
