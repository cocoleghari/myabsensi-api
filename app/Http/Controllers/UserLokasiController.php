<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Lokasi;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserLokasiController extends Controller
{
    public function __construct(private FaceRecognitionService $faceService) {}

    public function getUserLokasi(Request $request)
    {
        try {
            $user = $request->user();
            Log::info('getUserLokasi - User ID: '.$user->id);

            $lokasis = Lokasi::where('user_id', $user->id)
                ->select('id', 'lokasi', 'koordinat')
                ->orderBy('lokasi')
                ->get();

            return response()->json($lokasis);

        } catch (\Exception $e) {
            Log::error('Error getUserLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi',
            ], 500);
        }
    }

    public function submitAbsensiOtomatis(Request $request)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('SUBMIT ABSENSI OTOMATIS');

        try {
            $user = $request->user();
            $tipe = $request->tipe_absen;
            $titikKoordinatKamu = $request->titik_koordinat_kamu;

            Log::info('User ID: '.$user->id);
            Log::info('Tipe Absen: '.$tipe);
            Log::info('Posisi User: '.$titikKoordinatKamu);

            // ── 1. Validasi input dasar ──────────────────────────────
            if (! $tipe || ! in_array($tipe, ['masuk', 'pulang'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe absen tidak valid',
                ], 422);
            }

            if (! $titikKoordinatKamu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Titik koordinat kamu wajib diisi',
                ], 422);
            }

            if (! $request->hasFile('foto_wajah')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto wajah wajib diupload',
                ], 422);
            }

            // ── 2. Validasi status absen hari ini ────────────────────
            $sudahAbsen = Absensi::where('user_id', $user->id)
                ->where('tipe_absen', $tipe)
                ->whereDate('waktu_absen', now()->toDateString())
                ->exists();

            if ($sudahAbsen) {
                return response()->json([
                    'success' => false,
                    'message' => "Anda sudah melakukan absen $tipe hari ini",
                ], 400);
            }

            if ($tipe == 'pulang') {
                $sudahMasuk = Absensi::where('user_id', $user->id)
                    ->where('tipe_absen', 'masuk')
                    ->whereDate('waktu_absen', now()->toDateString())
                    ->exists();

                if (! $sudahMasuk) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda harus absen masuk terlebih dahulu',
                    ], 400);
                }
            }

            // ── 3. Cek wajah user sudah terdaftar ───────────────────
            if (! $user->wajah_terdaftar || ! $user->foto_wajah_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah Anda belum terdaftar. Hubungi admin untuk mendaftarkan wajah terlebih dahulu.',
                ], 403);
            }

            // ── 4. Validasi format koordinat ─────────────────────────
            $userParts = explode(',', $titikKoordinatKamu);
            if (count($userParts) != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format koordinat tidak valid',
                ], 422);
            }

            $userLat = floatval(trim($userParts[0]));
            $userLng = floatval(trim($userParts[1]));

            // ── 5. Cek radius lokasi ─────────────────────────────────
            $lokasis = Lokasi::where('user_id', $user->id)->get();

            if ($lokasis->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum memiliki lokasi absensi. Hubungi admin.',
                ], 404);
            }

            $lokasiTerdekat = null;
            $jarakTerdekat = PHP_FLOAT_MAX;

            foreach ($lokasis as $lokasi) {
                $lokasiParts = explode(',', $lokasi->koordinat);
                if (count($lokasiParts) != 2) {
                    continue;
                }

                $jarak = $this->hitungJarak(
                    $userLat, $userLng,
                    floatval(trim($lokasiParts[0])),
                    floatval(trim($lokasiParts[1]))
                );

                Log::info("Jarak ke {$lokasi->lokasi}: {$jarak} meter");

                if ($jarak < $jarakTerdekat) {
                    $jarakTerdekat = $jarak;
                    $lokasiTerdekat = [
                        'id' => $lokasi->id,
                        'lokasi' => $lokasi->lokasi,
                        'koordinat' => $lokasi->koordinat,
                        'jarak' => round($jarak, 2),
                        'dalam_radius' => $jarak <= 100,
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
                        'batas_radius' => 100,
                        'lokasi_terdekat' => $lokasiTerdekat,
                    ],
                ], 403);
            }

            // ── 6. Simpan foto absen sementara ───────────────────────
            $file = $request->file('foto_wajah');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ['jpg', 'jpeg', 'png'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format foto harus JPG, JPEG, atau PNG',
                ], 422);
            }

            $namaFileSementara = "temp_{$tipe}_{$user->id}_".time().".{$extension}";
            $pathSementara = $file->storeAs('foto_absensi_temp', $namaFileSementara, 'local');

            // ── 7. Face recognition ──────────────────────────────────
            Log::info('Memulai face recognition untuk user: '.$user->id);

            $pathAbsenAbsolut = Storage::disk('local')->path($pathSementara);
            $pathReferensiAbsolut = Storage::disk('public')->path($user->foto_wajah_path);

            Log::info('Path absen: '.$pathAbsenAbsolut);
            Log::info('Path referensi: '.$pathReferensiAbsolut);

            $hasilVerifikasi = $this->faceService->verifyByPath(
                $pathAbsenAbsolut,
                $pathReferensiAbsolut
            );

            Log::info('Hasil face recognition:', $hasilVerifikasi);

            if (! $hasilVerifikasi['verified']) {
                // Hapus foto temp jika verifikasi gagal
                Storage::delete($pathSementara);

                return response()->json([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal: '.$hasilVerifikasi['message'],
                    'confidence' => round($hasilVerifikasi['confidence'] * 100, 1).'%',
                ], 403);
            }

            // ── 8. Pindahkan foto ke folder permanen ─────────────────
            $namaFilePermanent = "{$tipe}_{$user->id}_".time().".{$extension}";
            $pathPermanent = "public/foto_absensi/{$namaFilePermanent}";

            Storage::move($pathSementara, $pathPermanent);

            $fotoUrl = config('app.url').Storage::url($pathPermanent);

            // ── 9. Simpan absensi ke database ────────────────────────
            DB::beginTransaction();

            try {
                $absensi = new Absensi;
                $absensi->user_id = $user->id;
                $absensi->lokasi_id = $lokasiTerdekat['id'];
                $absensi->titik_koordinat_lokasi = $lokasiTerdekat['koordinat'];
                $absensi->titik_koordinat_kamu = $titikKoordinatKamu;
                $absensi->foto_wajah = $fotoUrl;
                $absensi->tipe_absen = $tipe;
                $absensi->waktu_absen = now();
                $absensi->jarak_absensi = $lokasiTerdekat['jarak'];
                $absensi->confidence_score = $hasilVerifikasi['confidence'];
                $absensi->wajah_cocok = true;
                $absensi->save();

                DB::commit();

                Log::info("Absensi {$tipe} berhasil:", [
                    'id' => $absensi->id,
                    'lokasi' => $lokasiTerdekat['lokasi'],
                    'jarak' => $lokasiTerdekat['jarak'].' meter',
                    'confidence' => round($hasilVerifikasi['confidence'] * 100, 1).'%',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Absen {$tipe} berhasil",
                    'data' => [
                        'id' => $absensi->id,
                        'tipe_absen' => $absensi->tipe_absen,
                        'waktu_absen' => $absensi->waktu_absen->toDateTimeString(),
                        'lokasi' => $lokasiTerdekat['lokasi'],
                        'jarak' => $lokasiTerdekat['jarak'],
                        'foto_wajah' => $absensi->foto_wajah,
                        'confidence' => round($hasilVerifikasi['confidence'] * 100, 1).'%',
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                // Hapus foto permanent jika DB gagal
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

    public function daftarkanWajah(Request $request)
    {
        $request->validate([
            'foto_wajah' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $user = $request->user();
            $file = $request->file('foto_wajah');
            $fileName = "wajah_user_{$user->id}.jpg";

            // Hapus foto lama jika ada
            if ($user->foto_wajah_path) {
                Storage::disk('public')->delete($user->foto_wajah_path);
            }

            // Simpan ke public disk — path yang disimpan: "wajah_referensi/wajah_user_2.jpg"
            $path = $file->storeAs('wajah_referensi', $fileName, 'public');

            $user->update([
                'foto_wajah_path' => $path,
                'wajah_terdaftar' => true,
            ]);

            // URL yang dihasilkan: http://192.168.1.5:8000/storage/wajah_referensi/wajah_user_2.jpg
            $url = Storage::disk('public')->url($path);

            Log::info("Wajah terdaftar - User: {$user->id}, Path: {$path}, URL: {$url}");

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

    public function cekStatusHariIni(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $absensiMasuk = Absensi::where('user_id', $userId)
                ->where('tipe_absen', 'masuk')
                ->whereDate('waktu_absen', now()->toDateString())
                ->with('lokasi')
                ->first();

            $absensiPulang = Absensi::where('user_id', $userId)
                ->where('tipe_absen', 'pulang')
                ->whereDate('waktu_absen', now()->toDateString())
                ->with('lokasi')
                ->first();

            return response()->json([
                'success' => true,
                'tanggal' => now()->toDateString(),
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
            $userId = $request->user()->id;

            $absensis = Absensi::where('user_id', $userId)
                ->with('lokasi')
                ->orderBy('waktu_absen', 'desc')
                ->get();

            return response()->json($absensis);

        } catch (\Exception $e) {
            Log::error('Error getRiwayatAbsensi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat absensi',
            ], 500);
        }
    }

    // Rumus Haversine — tidak berubah dari kode existing
    private function hitungJarak($lat1, $lon1, $lat2, $lon2): float
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

    public function verifikasiWajahSaja(Request $request)
    {
        $request->validate(['foto_wajah' => 'required|image|max:5120']);

        $user = $request->user();

        if (! $user->wajah_terdaftar || ! $user->foto_wajah_path) {
            return response()->json([
                'verified' => false,
                'confidence' => 0,
                'message' => 'Wajah belum terdaftar',
            ]);
        }

        // Simpan foto absen sementara di local disk
        $pathTemp = $request->file('foto_wajah')
            ->storeAs('foto_absensi_temp', "verify_{$user->id}_".time().'.jpg', 'local');

        // Path absolut foto referensi dari public disk
        $pathReferensi = Storage::disk('public')->path($user->foto_wajah_path);
        $pathAbsen = Storage::disk('local')->path($pathTemp);

        Log::info("Verifikasi wajah - Referensi: {$pathReferensi}");
        Log::info("Verifikasi wajah - Absen: {$pathAbsen}");

        // Cek apakah file referensi ada
        if (! file_exists($pathReferensi)) {
            Storage::disk('local')->delete($pathTemp);
            Log::error("File referensi tidak ditemukan: {$pathReferensi}");

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
    }
}
