<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Employee;
use App\Models\EmployeePusatLokasi;
use App\Models\EmployeeShift;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserLokasiController extends Controller
{
    public function __construct(private FaceRecognitionService $faceService) {}

    // =========================================================================
    // HELPER PRIVATE
    // =========================================================================

    /**
     * Ambil employee dari user yang sedang login.
     * Throw jika belum ada profil karyawan.
     */
    private function getEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            abort(403, 'Profil karyawan tidak ditemukan. Hubungi admin.');
        }

        return $employee;
    }

    /**
     * Ambil shift aktif karyawan hari ini.
     * Return null jika belum di-assign shift.
     */
    private function getShiftAktif(Employee $employee): ?\App\Models\Shift
    {
        $employeeShift = EmployeeShift::aktifPada(now())
            ->where('employee_id', $employee->id)
            ->with(['shift', 'pattern.days.shift'])
            ->first();

        if (! $employeeShift) {
            return null;
        }

        // Mode 1: shift langsung
        if ($employeeShift->shift_id && $employeeShift->shift) {
            return $employeeShift->shift;
        }

        // Mode 2: pola shift mingguan
        if ($employeeShift->pattern_id && $employeeShift->pattern) {
            return $employeeShift->pattern->getShiftForDate(now());
        }

        return null;
    }

    /**
     * Buat nama file yang aman dari nama karyawan.
     */
    private function namaFileAman(Employee $employee): string
    {
        $nama = $employee->nickname ?? $employee->full_name ?? 'karyawan';
        $nama = strtolower($nama);
        $nama = preg_replace('/\s+/', '_', $nama);
        $nama = preg_replace('/[^a-z0-9_]/', '', $nama);
        $nama = trim($nama, '_');

        return $nama ?: 'employee_'.$employee->id;
    }

    /**
     * Rumus Haversine — hitung jarak dua koordinat dalam meter.
     */
    private function hitungJarak(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    // =========================================================================
    // LOKASI USER
    // =========================================================================

    /**
     * Ambil daftar pusat lokasi yang terdaftar untuk karyawan ini.
     * Menggunakan pivot employee_pusat_lokasi (menggantikan tabel lokasis).
     */
    public function getUserLokasi(Request $request)
    {
        try {
            $employee = $this->getEmployee($request);

            // Ambil via pivot, sertakan data pusat lokasi
            $lokasis = EmployeePusatLokasi::where('employee_id', $employee->id)
                ->with('pusatLokasi:id,nama_lokasi,titik_kordinat,is_active')
                ->get()
                ->map(function ($pivot) {
                    $koordinat = $pivot->pusatLokasi?->getKoordinatArray() ?? ['lat' => 0, 'lng' => 0];

                    return [
                        'id' => $pivot->id,                          // id pivot
                        'pusat_lokasi_id' => $pivot->pusat_lokasi_id,
                        'nama_lokasi' => $pivot->pusatLokasi?->nama_lokasi,
                        'titik_kordinat' => $pivot->pusatLokasi?->titik_kordinat,
                        'latitude' => $koordinat['lat'],
                        'longitude' => $koordinat['lng'],
                        'radius_meter' => $pivot->radius_meter,
                        'is_active' => $pivot->pusatLokasi?->is_active ?? false,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $lokasis,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getUserLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi',
            ], 500);
        }
    }

    // =========================================================================
    // ABSENSI
    // =========================================================================

    public function submitAbsensiOtomatis(Request $request)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('SUBMIT ABSENSI OTOMATIS');

        try {
            $employee = $this->getEmployee($request);
            $tipe = $request->tipe_absen;
            $userLat = floatval($request->latitude);
            $userLng = floatval($request->longitude);

            Log::info('Employee ID: '.$employee->id);
            Log::info('Tipe Absen: '.$tipe);
            Log::info("Koordinat User: {$userLat}, {$userLng}");

            // ── 1. Validasi input dasar ──────────────────────────────
            if (! $tipe || ! in_array($tipe, ['masuk', 'pulang'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe absen tidak valid',
                ], 422);
            }

            if (! $request->filled('latitude') || ! $request->filled('longitude')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koordinat latitude dan longitude wajib diisi',
                ], 422);
            }

            if (! $request->hasFile('foto_wajah')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto wajah wajib diupload',
                ], 422);
            }

            // ── 2. Ambil shift aktif ─────────────────────────────────
            $shift = $this->getShiftAktif($employee);

            // Jika absensi offline, gunakan waktu asli dari client
            $isOffline = $request->boolean('is_offline', false);
            $sekarang = $isOffline && $request->filled('waktu_absen')
                ? \Carbon\Carbon::parse($request->waktu_absen)->setTimezone('Asia/Jakarta')
                : now()->setTimezone('Asia/Jakarta');

            Log::info('Mode: '.($isOffline ? 'OFFLINE (waktu asli: '.$sekarang.')' : 'ONLINE'));

            // ── BARU: auto-convert ke "pulang" jika lupa absen masuk ────────
            // Berlaku hanya shift reguler. Jika user belum absen masuk sama
            // sekali dan sudah lewat ambang batas dari jam_masuk, anggap saja
            // niat aslinya absen pulang — tanpa dialog, tanpa approval manual.
            // ── BARU: auto-convert ke "pulang" jika lupa absen masuk ────────
            $BATAS_LUPA_MASUK_MENIT = 240; // 4 jam
            $isAutoConvertLupaMasuk = false;

            if ($tipe === 'masuk' && $shift && ! $shift->isFlex()) {
                $tanggalCekSementara = $shift->tanggalLogisAbsensi($sekarang);
                $sudahAdaMasuk = Absensi::where('employee_id', $employee->id)
                    ->where('tipe_absen', 'masuk')
                    ->whereDate('tanggal_absen', $tanggalCekSementara->toDateString())
                    ->exists();

                if (! $sudahAdaMasuk && $shift->sudahLewatBatasDariJamMasuk($sekarang, $BATAS_LUPA_MASUK_MENIT)) {
                    Log::info("Auto-convert masuk→pulang: employee {$employee->id} dianggap lupa absen masuk.");
                    $tipe = 'pulang';
                    $isAutoConvertLupaMasuk = true;
                }
            }

            // Untuk flex shift, cek apakah ini absen PULANG dari shift malam kemarin
            if ($shift && $shift->isFlex() && $tipe === 'pulang') {
                $kemarin = $sekarang->copy()->subDay()->startOfDay();
                $absenMasukKemarin = Absensi::where('employee_id', $employee->id)
                    ->where('tipe_absen', 'masuk')
                    ->whereDate('tanggal_absen', $kemarin->toDateString())
                    ->exists();
                $absenPulangKemarin = Absensi::where('employee_id', $employee->id)
                    ->where('tipe_absen', 'pulang')
                    ->whereDate('tanggal_absen', $kemarin->toDateString())
                    ->exists();

                // Masuk kemarin, belum pulang → pulang ini milik shift kemarin
                if ($absenMasukKemarin && ! $absenPulangKemarin) {
                    $tanggalAbsen = $kemarin;
                } else {
                    $tanggalAbsen = $sekarang->copy()->startOfDay();
                }
            } else {
                $tanggalAbsen = $shift
                    ? $shift->tanggalLogisAbsensi($sekarang)
                    : $sekarang->copy()->startOfDay();
            }
            Log::info('DEBUG SEKARANG', [
                'sekarang' => $sekarang->toDateTimeString(),
                'timezone' => $sekarang->timezone->getName(),
                'startOfDay' => $sekarang->copy()->startOfDay()->toDateTimeString(),
                'tanggalAbsen' => $tanggalAbsen->toDateString(),
            ]);

            Log::info('Shift: '.($shift?->nama ?? 'Tidak ada shift'));
            Log::info('Tanggal Logis Absen: '.$tanggalAbsen->toDateString());

            // ── 3. Validasi status absen pada tanggal logis ──────────
            $sudahAbsen = Absensi::where('employee_id', $employee->id)
                ->where('tipe_absen', $tipe)
                ->whereDate('tanggal_absen', $tanggalAbsen->toDateString())
                ->exists();

            if ($sudahAbsen) {
                return response()->json([
                    'success' => false,
                    'message' => "Anda sudah melakukan absen {$tipe} hari ini",
                ], 400);
            }

            if ($tipe === 'pulang' && ! $isAutoConvertLupaMasuk) {
                $sudahMasuk = Absensi::where('employee_id', $employee->id)
                    ->where('tipe_absen', 'masuk')
                    ->whereDate('tanggal_absen', $tanggalAbsen->toDateString())
                    ->exists();

                if (! $sudahMasuk) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda harus absen masuk terlebih dahulu',
                    ], 400);
                }
            }

            // ── 4. Validasi wajah sudah terdaftar ───────────────────
            if (! $employee->wajah_terdaftar || ! $employee->foto_wajah_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah Anda belum terdaftar. Hubungi admin untuk mendaftarkan wajah terlebih dahulu.',
                ], 403);
            }

            // ── 5. Validasi format foto ──────────────────────────────
            $file = $request->file('foto_wajah');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ['jpg', 'jpeg', 'png'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format foto harus JPG, JPEG, atau PNG',
                ], 422);
            }

            // ── 6. Cek radius lokasi (via pivot employee_pusat_lokasi) ──
            $pivotLokasis = EmployeePusatLokasi::where('employee_id', $employee->id)
                ->with('pusatLokasi')
                ->get();

            if ($pivotLokasis->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum memiliki lokasi absensi. Hubungi admin.',
                ], 404);
            }

            $lokasiTerdekat = null;
            $jarakTerdekat = PHP_FLOAT_MAX;

            foreach ($pivotLokasis as $pivot) {
                $pusatLokasi = $pivot->pusatLokasi;

                if (! $pusatLokasi || ! $pusatLokasi->is_active) {
                    continue;
                }

                $koordinat = $pusatLokasi->getKoordinatArray();
                $jarak = $this->hitungJarak(
                    $userLat, $userLng,
                    (float) $koordinat['lat'],
                    (float) $koordinat['lng']
                );

                Log::info("Jarak ke {$pusatLokasi->nama_lokasi}: {$jarak} meter (radius: {$pivot->radius_meter}m)");

                if ($jarak < $jarakTerdekat) {
                    $jarakTerdekat = $jarak;
                    $lokasiTerdekat = [
                        'pusat_lokasi_id' => $pusatLokasi->id,
                        'nama_lokasi' => $pusatLokasi->nama_lokasi,
                        'jarak' => round($jarak, 2),
                        'radius_meter' => $pivot->radius_meter,
                        'dalam_radius' => $jarak <= $pivot->radius_meter,
                    ];
                }
            }

            if (! $lokasiTerdekat || ! $lokasiTerdekat['dalam_radius']) {
                Log::warning("Di luar radius. Jarak terdekat: {$jarakTerdekat} meter");

                return response()->json([
                    'success' => false,
                    'message' => 'Anda berada di luar jangkauan absen',
                    'data' => [
                        'jarak_terdekat' => round($jarakTerdekat, 2),
                        'batas_radius' => $lokasiTerdekat['radius_meter'] ?? 100,
                        'lokasi_terdekat' => $lokasiTerdekat,
                    ],
                ], 403);
            }

            // ── 7. Simpan foto sementara ─────────────────────────────
            $namaFile = $this->namaFileAman($employee);
            $namaFileSementara = "temp_{$tipe}_{$namaFile}_".time().".{$extension}";
            $pathSementara = $file->storeAs('foto_absensi_temp', $namaFileSementara, 'local');

            // ── 8. Face recognition ──────────────────────────────────
            Log::info('Memulai face recognition untuk employee: '.$employee->id);

            $pathAbsenAbsolut = Storage::disk('local')->path($pathSementara);
            $pathReferensiAbsolut = Storage::disk('public')->path($employee->foto_wajah_path);

            if (! file_exists($pathReferensiAbsolut)) {
                Storage::disk('local')->delete($pathSementara);
                Log::error("File referensi tidak ditemukan: {$pathReferensiAbsolut}");

                return response()->json([
                    'success' => false,
                    'message' => 'File foto referensi tidak ditemukan. Hubungi admin.',
                ], 500);
            }

            $hasilVerifikasi = $this->faceService->verifyByPath(
                $pathAbsenAbsolut,
                $pathReferensiAbsolut
            );

            Log::info('Hasil face recognition:', $hasilVerifikasi);

            if (! $hasilVerifikasi['verified']) {
                Storage::disk('local')->delete($pathSementara);

                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal: '.$hasilVerifikasi['message'],
                    'confidence' => round($hasilVerifikasi['confidence'] * 100, 1).'%',
                ], 403);
            }

            // ── 9. Pindahkan foto ke folder permanen ─────────────────
            $namaFilePermanent = "{$tipe}_{$namaFile}_".time().".{$extension}";
            $pathPermanent = "public/foto_absensi/{$namaFilePermanent}";
            Storage::move($pathSementara, $pathPermanent);

            // ── 10. Kalkulasi shift ───────────────────────────────────
            $menitTerlambat = 0;
            $menitLembur = 0;
            $statusAbsen = 'tepat_waktu';

            if ($shift) {
                if ($tipe === 'masuk') {
                    $menitTerlambat = $shift->hitungMenitTerlambat($sekarang);
                    if ($shift->isFlex()) {
                        $statusAbsen = 'hadir';
                    } else {
                        $statusAbsen = $menitTerlambat > 0 ? 'terlambat' : 'tepat_waktu';
                    }
                } else {
                    $menitLembur = $shift->hitungMenitLembur($sekarang);
                    if ($shift->isFlex()) {
                        $statusAbsen = 'hadir';
                    } else {
                        $statusAbsen = $menitLembur > 0 ? 'lembur' : 'tepat_waktu';
                    }
                }
            } elseif (! $shift) {
                // Karyawan tanpa shift juga cukup dicatat sebagai hadir
                $statusAbsen = 'hadir';
            }

            Log::info("Status: {$statusAbsen}, Terlambat: {$menitTerlambat} menit, Lembur: {$menitLembur} menit");

            // ── 11. Simpan ke database ────────────────────────────────
            DB::beginTransaction();

            try {
                $absensi = Absensi::create([
                    'employee_id' => $employee->id,
                    'pusat_lokasi_id' => $lokasiTerdekat['pusat_lokasi_id'],
                    'shift_id' => $shift?->id,
                    'tanggal_absen' => $tanggalAbsen->toDateString(),
                    'tipe_absen' => $tipe,
                    'waktu_absen' => $sekarang,
                    'latitude' => $userLat,
                    'longitude' => $userLng,
                    'jarak_meter' => $lokasiTerdekat['jarak'],
                    'foto_absen_path' => $namaFilePermanent,
                    'confidence_score' => $hasilVerifikasi['confidence'],
                    'wajah_cocok' => true,
                    'status' => $statusAbsen,
                    'menit_terlambat' => $menitTerlambat,
                    'menit_lembur' => $menitLembur,
                    'catatan' => $request->input('catatan'), // ← TAMBAH INI
                ]);

                DB::commit();

                Log::info("Absensi {$tipe} berhasil:", [
                    'id' => $absensi->id,
                    'lokasi' => $lokasiTerdekat['nama_lokasi'],
                    'jarak' => $lokasiTerdekat['jarak'].' meter',
                    'status' => $statusAbsen,
                    'confidence' => round($hasilVerifikasi['confidence'] * 100, 1).'%',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Absen {$tipe} berhasil",
                    'data' => [
                        'id' => $absensi->id,
                        'tipe_absen' => $absensi->tipe_absen,
                        'tanggal_absen' => $absensi->tanggal_absen->toDateString(),
                        'waktu_absen' => $absensi->waktu_absen->toDateTimeString(),
                        'lokasi' => $lokasiTerdekat['nama_lokasi'],
                        'jarak' => $lokasiTerdekat['jarak'],
                        'shift' => $shift?->nama,
                        'status' => $statusAbsen,
                        'menit_terlambat' => $menitTerlambat,
                        'menit_lembur' => $menitLembur,
                        'confidence' => round($hasilVerifikasi['confidence'] * 100, 1).'%',
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Storage::delete($pathPermanent);
                Log::error('Error saat menyimpan absensi: '.$e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error submitAbsensiOtomatis: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan absensi: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // WAJAH
    // =========================================================================

    public function daftarkanWajah(Request $request)
    {
        $request->validate([
            'foto_wajah' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $employee = $this->getEmployee($request);
            $file = $request->file('foto_wajah');

            // Hapus foto lama jika ada
            if ($employee->foto_wajah_path) {
                Storage::disk('public')->delete($employee->foto_wajah_path);
            }

            $namaFile = $this->namaFileAman($employee);
            $fileName = 'wajah_'.$namaFile.'.jpg';
            $path = $file->storeAs('wajah_referensi', $fileName, 'public');

            $employee->update([
                'foto_wajah_path' => $path,
                'wajah_terdaftar' => true,
            ]);

            $url = Storage::disk('public')->url($path);

            Log::info("Wajah terdaftar - Employee: {$employee->id}, Path: {$path}");

            return response()->json([
                'success' => true,
                'message' => 'Wajah berhasil didaftarkan',
                'foto_wajah_url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('Error daftarkanWajah: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan wajah: '.$e->getMessage(),
            ], 500);
        }
    }

    public function verifikasiWajahSaja(Request $request)
    {
        $request->validate(['foto_wajah' => 'required|image|max:5120']);

        try {
            $employee = $this->getEmployee($request);

            if (! $employee->wajah_terdaftar || ! $employee->foto_wajah_path) {
                return response()->json([
                    'verified' => false,
                    'confidence' => 0,
                    'message' => 'Wajah belum terdaftar',
                ]);
            }

            $namaFile = $this->namaFileAman($employee);
            $pathTemp = $request->file('foto_wajah')
                ->storeAs('foto_absensi_temp', "verify_{$namaFile}_".time().'.jpg', 'local');

            $pathReferensi = Storage::disk('public')->path($employee->foto_wajah_path);
            $pathAbsen = Storage::disk('local')->path($pathTemp);

            if (! file_exists($pathReferensi)) {
                Storage::disk('local')->delete($pathTemp);

                return response()->json([
                    'verified' => false,
                    'confidence' => 0,
                    'message' => 'File foto referensi tidak ditemukan',
                ]);
            }

            $hasil = $this->faceService->verifyByPath($pathAbsen, $pathReferensi);
            Storage::disk('local')->delete($pathTemp);

            return response()->json([
                'verified' => $hasil['verified'],
                'confidence' => $hasil['confidence'],
                'message' => $hasil['message'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error verifikasiWajahSaja: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal verifikasi wajah: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // STATUS & RIWAYAT
    // =========================================================================

    public function cekStatusHariIni(Request $request)
    {
        try {
            $employee = $this->getEmployee($request);
            $shift = $this->getShiftAktif($employee);
            $sekarang = now()->setTimezone('Asia/Jakarta');

            // ── Tentukan tanggal logis ──────────────────────────────────
            if ($shift && $shift->isFlex()) {
                // Flex: cek dulu apakah ada absen masuk KEMARIN yang belum pulang
                $kemarin = $sekarang->copy()->subDay()->startOfDay();

                $absenMasukKemarin = Absensi::where('employee_id', $employee->id)
                    ->where('tipe_absen', 'masuk')
                    ->whereDate('tanggal_absen', $kemarin->toDateString())
                    ->first();

                $absenPulangKemarin = Absensi::where('employee_id', $employee->id)
                    ->where('tipe_absen', 'pulang')
                    ->whereDate('tanggal_absen', $kemarin->toDateString())
                    ->first();

                // Kalau kemarin sudah masuk tapi belum pulang
                // → karyawan sedang dalam shift malam yang lintas hari
                // → gunakan tanggal kemarin sebagai konteks
                if ($absenMasukKemarin && ! $absenPulangKemarin) {
                    $tanggalAbsen = $kemarin;

                    return response()->json([
                        'success' => true,
                        'tanggal' => $tanggalAbsen->toDateString(),
                        'shift' => $shift ? [
                            'nama' => $shift->nama,
                            'jam_masuk' => $shift->jam_masuk,
                            'jam_pulang' => $shift->jam_pulang,
                            'tipe' => $shift->tipe,
                            'toleransi_terlambat_menit' => $shift->toleransi_terlambat_menit,
                        ] : null,
                        'sudah_masuk' => true,
                        'sudah_pulang' => false,
                        'data_masuk' => $absenMasukKemarin->load('pusatLokasi:id,nama_lokasi'),
                        'data_pulang' => null,
                    ]);
                }

                // Tidak ada shift malam kemarin → pakai hari ini
                $tanggalAbsen = $sekarang->copy()->startOfDay();

            } else {
                // Reguler: pakai tanggal logis dari shift
                $tanggalAbsen = $shift
                    ? $shift->tanggalLogisAbsensi($sekarang)
                    : $sekarang->copy()->startOfDay();
            }

            // ── Cari absensi berdasarkan tanggal logis ──────────────────
            $absensiMasuk = Absensi::where('employee_id', $employee->id)
                ->where('tipe_absen', 'masuk')
                ->whereDate('tanggal_absen', $tanggalAbsen->toDateString())
                ->with('pusatLokasi:id,nama_lokasi')
                ->first();

            $absensiPulang = Absensi::where('employee_id', $employee->id)
                ->where('tipe_absen', 'pulang')
                ->whereDate('tanggal_absen', $tanggalAbsen->toDateString())
                ->with('pusatLokasi:id,nama_lokasi')
                ->first();

            return response()->json([
                'success' => true,
                'tanggal' => $tanggalAbsen->toDateString(),
                'shift' => $shift ? [
                    'nama' => $shift->nama,
                    'jam_masuk' => $shift->jam_masuk,
                    'jam_pulang' => $shift->jam_pulang,
                    'tipe' => $shift->tipe,
                    'toleransi_terlambat_menit' => $shift->toleransi_terlambat_menit,
                ] : null,
                'sudah_masuk' => (bool) $absensiMasuk,
                'sudah_pulang' => (bool) $absensiPulang,
                'data_masuk' => $absensiMasuk,
                'data_pulang' => $absensiPulang,
            ]);

        } catch (\Exception $e) {
            Log::error('Error cekStatusHariIni: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal cek status',
            ], 500);
        }
    }

    public function getRiwayatAbsensi(Request $request)
    {
        try {
            $employee = $this->getEmployee($request);

            $absensis = Absensi::where('employee_id', $employee->id)
                ->with([
                    'pusatLokasi:id,nama_lokasi',
                    'shift:id,nama,kode,jam_masuk,jam_pulang',
                    // Relasi ke permintaan untuk ambil alamat_pengajuan
                    'permintaanAbsen:id,absensi_id,alamat_pengajuan',
                ])
                ->orderBy('tanggal_absen', 'desc')
                ->orderBy('waktu_absen', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $absensis,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getRiwayatAbsensi: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Gagal mengambil riwayat absensi'], 500);
        }
    }
}
