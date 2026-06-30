<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'tipe',          // ← BARU
        'nama',
        'kode',
        'jam_masuk',
        'jam_pulang',
        'toleransi_terlambat_menit',
        'window_masuk_awal_menit',
        'melewati_tengah_malam',
        'batas_waktu_pulang',
        'berlaku_hari_libur',
        'berlaku_akhir_pekan',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tipe' => 'string',
        'melewati_tengah_malam' => 'boolean',
        'berlaku_hari_libur' => 'boolean',
        'berlaku_akhir_pekan' => 'boolean',
        'is_active' => 'boolean',
        'toleransi_terlambat_menit' => 'integer',
        'window_masuk_awal_menit' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    // -------------------------------------------------------------------------
    // Helper: Flex
    // -------------------------------------------------------------------------

    public function isFlex(): bool
    {
        return $this->tipe === 'flex';
    }

    // -------------------------------------------------------------------------
    // Logika Shift
    // -------------------------------------------------------------------------

    /**
     * Cek apakah tombol absen MASUK boleh ditampilkan pada waktu tertentu.
     * Flex shift: selalu boleh (window 24 jam penuh).
     */
    public function bolehAbsenMasuk(Carbon $sekarang): bool
    {
        if ($this->isFlex()) {
            return true;
        }

        $jamMasuk = Carbon::parse($this->jam_masuk);
        $waktuAwal = $jamMasuk->copy()->subMinutes($this->window_masuk_awal_menit);
        $batas = Carbon::parse($this->batas_waktu_pulang);

        $waktuHariIni = $sekarang->copy()->startOfDay();
        $waktuAwal = $waktuHariIni->copy()->setTimeFromTimeString($waktuAwal->format('H:i:s'));
        $batasFull = $waktuHariIni->copy()->setTimeFromTimeString($batas->format('H:i:s'));

        if ($this->melewati_tengah_malam || $batas->lt($jamMasuk)) {
            $batasFull->addDay();
        }

        return $sekarang->between($waktuAwal, $batasFull);
    }

    /**
     * Hitung menit keterlambatan.
     * Flex shift: selalu 0 (tidak ada konsep terlambat).
     */
    public function hitungMenitTerlambat(Carbon $waktuAbsen): int
    {
        if ($this->isFlex()) {
            return 0;
        }

        $jamMasuk = Carbon::parse($this->jam_masuk);
        $batasTepatWaktu = $waktuAbsen->copy()->startOfDay()
            ->setTimeFromTimeString($jamMasuk->format('H:i:s'))
            ->addMinutes($this->toleransi_terlambat_menit);

        if ($waktuAbsen->lte($batasTepatWaktu)) {
            return 0;
        }

        $acuan = $waktuAbsen->copy()->startOfDay()
            ->setTimeFromTimeString($jamMasuk->format('H:i:s'));

        return (int) $acuan->diffInMinutes($waktuAbsen);
    }

    /**
     * Hitung menit lembur.
     * Flex shift: selalu 0 (tidak ada konsep lembur).
     */
    public function hitungMenitLembur(Carbon $waktuPulang): int
    {
        if ($this->isFlex()) {
            return 0;
        }

        $jamPulang = Carbon::parse($this->jam_pulang);
        $acuan = $waktuPulang->copy()->startOfDay()
            ->setTimeFromTimeString($jamPulang->format('H:i:s'));

        if ($this->melewati_tengah_malam) {
            $acuan->addDay();
        }

        if ($waktuPulang->lte($acuan)) {
            return 0;
        }

        return (int) $acuan->diffInMinutes($waktuPulang);
    }

    /**
     * Tentukan tanggal LOGIS absensi.
     * Flex shift: selalu tanggal hari ini (tidak ada logika lintas malam).
     */
    public function tanggalLogisAbsensi(Carbon $waktuAbsen): Carbon
    {
        if ($this->isFlex()) {
            return $waktuAbsen->copy()->startOfDay();
        }

        if (! $this->melewati_tengah_malam) {
            return $waktuAbsen->copy()->startOfDay();
        }

        $batasDiniHari = Carbon::parse($this->batas_waktu_pulang);
        $tengahMalam = $waktuAbsen->copy()->startOfDay();
        $batasFull = $tengahMalam->copy()->setTimeFromTimeString($batasDiniHari->format('H:i:s'));

        if ($waktuAbsen->between($tengahMalam, $batasFull)) {
            return $waktuAbsen->copy()->subDay()->startOfDay();
        }

        return $waktuAbsen->copy()->startOfDay();
    }

    /**
     * Apakah sudah waktunya reset?
     * Flex shift: reset setiap tengah malam (00:00).
     */
    public function sudahHariBerikutnya(Carbon $sekarang): bool
    {
        if ($this->isFlex()) {
            return false; // flex tidak perlu dicek, selalu reset di tengah malam biasa
        }

        $batas = Carbon::parse($this->batas_waktu_pulang);
        $batasFull = $sekarang->copy()->startOfDay()
            ->setTimeFromTimeString($batas->format('H:i:s'));

        if ($this->melewati_tengah_malam) {
            $batasFull->addDay();
        }

        return $sekarang->gt($batasFull);
    }

    /**
     * Cek apakah waktu yang diberikan sudah lewat [$batasMenit] menit
     * dari jam_masuk shift. Shift flex selalu false (tidak ada konsep
     * jam masuk tetap untuk diukur "terlambat parah").
     */
    public function sudahLewatBatasDariJamMasuk(Carbon $waktu, int $batasMenit): bool
    {
        if ($this->isFlex()) {
            return false;
        }

        $jamMasuk = Carbon::parse($this->jam_masuk);
        $batasWaktu = $waktu->copy()->startOfDay()
            ->setTimeFromTimeString($jamMasuk->format('H:i:s'))
            ->addMinutes($batasMenit);

        return $waktu->gt($batasWaktu);
    }
}
