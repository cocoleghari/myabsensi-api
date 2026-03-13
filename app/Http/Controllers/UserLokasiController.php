<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserLokasiController extends Controller
{
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
        Log::info('Request data:', $request->all());

        try {
            $user = $request->user();
            $tipe = $request->tipe_absen;
            $titikKoordinatKamu = $request->titik_koordinat_kamu;

            Log::info('User ID: '.$user->id);
            Log::info('Tipe Absen: '.$tipe);
            Log::info('Posisi User: '.$titikKoordinatKamu);

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

            $userParts = explode(',', $titikKoordinatKamu);
            if (count($userParts) != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format koordinat user tidak valid',
                ], 422);
            }

            $userLat = floatval(trim($userParts[0]));
            $userLng = floatval(trim($userParts[1]));

            $lokasis = Lokasi::where('user_id', $user->id)->get();

            if ($lokasis->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum memiliki lokasi absensi. Hubungi admin.',
                ], 404);
            }

            $lokasiTerdekat = null;
            $jarakTerdekat = PHP_FLOAT_MAX;
            $lokasiDalamRadius = [];

            foreach ($lokasis as $lokasi) {

                $lokasiParts = explode(',', $lokasi->koordinat);
                if (count($lokasiParts) != 2) {
                    continue;
                }

                $lokasiLat = floatval(trim($lokasiParts[0]));
                $lokasiLng = floatval(trim($lokasiParts[1]));

                $jarak = $this->hitungJarak($userLat, $userLng, $lokasiLat, $lokasiLng);

                Log::info("Jarak ke {$lokasi->lokasi}: {$jarak} meter");

                $lokasiData = [
                    'id' => $lokasi->id,
                    'lokasi' => $lokasi->lokasi,
                    'koordinat' => $lokasi->koordinat,
                    'jarak' => round($jarak, 2),
                    'dalam_radius' => $jarak <= 100,
                ];

                $lokasiDalamRadius[] = $lokasiData;

                if ($jarak < $jarakTerdekat) {
                    $jarakTerdekat = $jarak;
                    $lokasiTerdekat = $lokasiData;
                }
            }

            $lokasiDalamRadius = array_filter($lokasiDalamRadius, function ($item) {
                return $item['dalam_radius'];
            });

            if (empty($lokasiDalamRadius)) {

                Log::warning("Tidak ada lokasi dalam radius. Jarak terdekat: {$jarakTerdekat} meter");

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

            $lokasiTerpilih = $lokasiTerdekat;

            $fotoUrl = null;
            if ($request->hasFile('foto_wajah')) {
                $file = $request->file('foto_wajah');

                $validExtensions = ['jpg', 'jpeg', 'png'];
                $extension = $file->getClientOriginalExtension();

                if (! in_array(strtolower($extension), $validExtensions)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format foto harus JPG, JPEG, atau PNG',
                    ], 422);
                }

                $fileName = $tipe.'_'.time().'_'.$user->id.'.'.$extension;

                $path = $file->storeAs('public/foto_absensi', $fileName);

                if ($path) {
                    $baseUrl = config('app.url');
                    $fotoUrl = $baseUrl.Storage::url($path);
                    Log::info('Foto tersimpan: '.$fotoUrl);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Foto wajah wajib diupload',
                ], 422);
            }

            DB::beginTransaction();

            try {
                $absensi = new Absensi;
                $absensi->user_id = $user->id;
                $absensi->lokasi_id = $lokasiTerpilih['id'];
                $absensi->titik_koordinat_lokasi = $lokasiTerpilih['koordinat'];
                $absensi->titik_koordinat_kamu = $titikKoordinatKamu;
                $absensi->foto_wajah = $fotoUrl;
                $absensi->tipe_absen = $tipe;
                $absensi->waktu_absen = now();
                $absensi->jarak_absensi = $lokasiTerpilih['jarak'];
                $absensi->save();

                DB::commit();

                Log::info('Absensi '.$tipe.' berhasil:', [
                    'id' => $absensi->id,
                    'lokasi' => $lokasiTerpilih['lokasi'],
                    'jarak' => $lokasiTerpilih['jarak'].' meter',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Absen '.$tipe.' berhasil',
                    'data' => [
                        'id' => $absensi->id,
                        'tipe_absen' => $absensi->tipe_absen,
                        'waktu_absen' => $absensi->waktu_absen->toDateTimeString(),
                        'lokasi' => $lokasiTerpilih['lokasi'],
                        'jarak' => $lokasiTerpilih['jarak'],
                        'foto_wajah' => $absensi->foto_wajah,
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
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

    /**
     * @param  float
     * @param  float
     * @param  float
     * @param  float
     * @return float Jarak dalam meter
     */

    // rumus haversine
    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        // angle menghitung antara 2 sudut bumi
        return $angle * $earthRadius;
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
                'sudah_masuk' => $absensiMasuk ? true : false,
                'sudah_pulang' => $absensiPulang ? true : false,
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
}
