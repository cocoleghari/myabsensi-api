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
    // Logika Shift
    // -------------------------------------------------------------------------

    /**
     * Cek apakah tombol absen MASUK boleh ditampilkan pada waktu tertentu.
     *
     * Tombol muncul sejak (jam_masuk - window_masuk_awal_menit) hingga
     * batas_waktu_pulang (termasuk lintas hari untuk shift malam).
     */
    public function bolehAbsenMasuk(Carbon $sekarang): bool
    {
        $jamMasuk = Carbon::parse($this->jam_masuk);

        // Waktu paling awal boleh absen masuk
        $waktuAwal = $jamMasuk->copy()->subMinutes($this->window_masuk_awal_menit);

        // Waktu paling akhir boleh absen masuk = jam pulang + batas lembur
        $batas = Carbon::parse($this->batas_waktu_pulang);

        // Normalkan ke hari yang sama untuk perbandingan
        $waktuHariIni = $sekarang->copy()->startOfDay();
        $waktuAwal = $waktuHariIni->copy()->setTimeFromTimeString($waktuAwal->format('H:i:s'));
        $batasFull = $waktuHariIni->copy()->setTimeFromTimeString($batas->format('H:i:s'));

        // Jika batas melewati tengah malam, tambah 1 hari
        if ($this->melewati_tengah_malam || $batas->lt($jamMasuk)) {
            $batasFull->addDay();
        }

        return $sekarang->between($waktuAwal, $batasFull);
    }

    /**
     * Hitung menit keterlambatan.
     * Return 0 jika tepat waktu atau dalam toleransi.
     */
    public function hitungMenitTerlambat(Carbon $waktuAbsen): int
    {
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
     * Hitung menit lembur saat absen pulang.
     * Return 0 jika pulang sebelum atau tepat jam pulang.
     */
    public function hitungMenitLembur(Carbon $waktuPulang): int
    {
        $jamPulang = Carbon::parse($this->jam_pulang);
        $acuan = $waktuPulang->copy()->startOfDay()
            ->setTimeFromTimeString($jamPulang->format('H:i:s'));

        // Shift melewati tengah malam: jam pulang ada di hari berikutnya
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
     *
     * Untuk shift malam: karyawan masuk Senin jam 22:00, pulang Selasa jam 01:00.
     * Tanggal logis tetap Senin agar rekap harian akurat.
     *
     * Caranya: jika waktu absen sekarang < jam_masuk shift (artinya ini sisi
     * "dini hari" dari shift semalam), maka tanggal logis = kemarin.
     */
    public function tanggalLogisAbsensi(Carbon $waktuAbsen): Carbon
    {
        if (! $this->melewati_tengah_malam) {
            return $waktuAbsen->copy()->startOfDay();
        }

        $jamMasuk = Carbon::parse($this->jam_masuk);
        $batasDiniHari = Carbon::parse($this->batas_waktu_pulang);

        // Jika waktu absen sekarang antara tengah malam dan batas pulang,
        // maka ini adalah "sisi dini hari" → tanggal logis adalah kemarin
        $tengahMalam = $waktuAbsen->copy()->startOfDay();
        $batasFull = $tengahMalam->copy()->setTimeFromTimeString($batasDiniHari->format('H:i:s'));

        if ($waktuAbsen->between($tengahMalam, $batasFull)) {
            return $waktuAbsen->copy()->subDay()->startOfDay();
        }

        return $waktuAbsen->copy()->startOfDay();
    }

    /**
     * Apakah sudah waktunya reset (hari dianggap berganti)?
     * Reset terjadi setelah batas_waktu_pulang terlewati.
     */
    public function sudahHariBerikutnya(Carbon $sekarang): bool
    {
        $batas = Carbon::parse($this->batas_waktu_pulang);
        $batasFull = $sekarang->copy()->startOfDay()
            ->setTimeFromTimeString($batas->format('H:i:s'));

        if ($this->melewati_tengah_malam) {
            $batasFull->addDay();
        }

        return $sekarang->gt($batasFull);
    }
}
