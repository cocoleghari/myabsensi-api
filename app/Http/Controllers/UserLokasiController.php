<?php

// app/Http/Controllers/UserLokasiController.php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserLokasiController extends Controller
{
    /**
     * Ambil lokasi untuk user yang sedang login
     */
    public function getUserLokasi(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                Log::error('User tidak ditemukan');

                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 401);
            }

            Log::info('getUserLokasi - User ID: '.$user->id);
            Log::info('getUserLokasi - User Role: '.$user->role);

            // Ambil lokasi berdasarkan user_id
            $lokasis = Lokasi::where('user_id', $user->id)
                ->select('id', 'lokasi', 'koordinat')
                ->orderBy('lokasi')
                ->get();

            Log::info('Jumlah lokasi ditemukan: '.$lokasis->count());

            return response()->json($lokasis);

        } catch (\Exception $e) {
            Log::error('Error getUserLokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit absensi masuk
     */
    public function submitAbsensiMasuk(Request $request)
    {
        return $this->submitAbsensi($request, 'masuk');
    }

    /**
     * Submit absensi pulang
     */
    public function submitAbsensiPulang(Request $request)
    {
        return $this->submitAbsensi($request, 'pulang');
    }

    /**
     * Submit absensi (general)
     */
    private function submitAbsensi(Request $request, $tipe)
    {
        Log::info('='.str_repeat('=', 50));
        Log::info('SUBMIT ABSENSI '.strtoupper($tipe).' DIPANGGIL');
        Log::info('Request all data: '.json_encode($request->all()));
        Log::info('Has file: '.($request->hasFile('foto_wajah') ? 'YA' : 'TIDAK'));

        try {
            $user = $request->user();

            if (! $user) {
                Log::error('User tidak ditemukan');

                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 401);
            }

            $lokasiId = $request->lokasi_id;
            $titikKoordinatKamu = $request->titik_koordinat_kamu;

            Log::info('User ID: '.$user->id);
            Log::info('Lokasi ID: '.$lokasiId);
            Log::info('Titik Koordinat Kamu: '.$titikKoordinatKamu);
            Log::info('Tipe Absen: '.$tipe);

            // Validasi input
            if (! $lokasiId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi ID wajib diisi',
                ], 422);
            }

            // Ambil data lokasi
            $lokasi = Lokasi::find($lokasiId);

            if (! $lokasi) {
                Log::warning("Lokasi $lokasiId tidak ditemukan");

                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi tidak ditemukan',
                ], 404);
            }

            // Cek kepemilikan
            if ($lokasi->user_id != $user->id) {
                Log::warning("Lokasi $lokasiId bukan milik user {$user->id}");

                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi bukan milik anda',
                ], 403);
            }

            // Cek apakah sudah absen hari ini (sesuai tipe)
            $sudahAbsen = Absensi::where('user_id', $user->id)
                ->where('tipe_absen', $tipe)
                ->whereDate('waktu_absen', now()->toDateString())
                ->exists();

            if ($sudahAbsen) {
                Log::warning("User {$user->id} sudah absen $tipe hari ini");

                return response()->json([
                    'success' => false,
                    'message' => "Anda sudah melakukan absen $tipe hari ini",
                ], 400);
            }

            // Upload foto jika ada
            $fotoUrl = null;
            if ($request->hasFile('foto_wajah')) {
                $file = $request->file('foto_wajah');

                // Validasi file
                $validExtensions = ['jpg', 'jpeg', 'png'];
                $extension = $file->getClientOriginalExtension();

                if (! in_array(strtolower($extension), $validExtensions)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format foto harus JPG, JPEG, atau PNG',
                    ], 422);
                }

                // Generate nama file unik
                $fileName = $tipe.'_'.time().'_'.$user->id.'.'.$extension;

                // Simpan file
                $path = $file->storeAs('public/foto_absensi', $fileName);

                if ($path) {
                    // Buat URL lengkap dengan base URL
                    $baseUrl = config('app.url');
                    $fotoUrl = $baseUrl.Storage::url($path);

                    Log::info('Foto tersimpan: '.$fotoUrl);
                } else {
                    Log::error('Gagal menyimpan foto');
                }
            } else {
                Log::warning('Tidak ada file foto yang dikirim');

                return response()->json([
                    'success' => false,
                    'message' => 'Foto wajah wajib diupload',
                ], 422);
            }       

            // Simpan absensi
            DB::beginTransaction();

            try {
                $absensi = new Absensi;
                $absensi->user_id = $user->id;
                $absensi->lokasi_id = $lokasiId;
                $absensi->titik_koordinat_lokasi = $lokasi->koordinat;
                $absensi->titik_koordinat_kamu = $titikKoordinatKamu;
                $absensi->foto_wajah = $fotoUrl;
                $absensi->tipe_absen = $tipe;
                $absensi->waktu_absen = now();
                $absensi->save();

                DB::commit();

                Log::info('Absensi '.$tipe.' berhasil: ID='.$absensi->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Absen '.$tipe.' berhasil',
                    'data' => [
                        'id' => $absensi->id,
                        'tipe_absen' => $absensi->tipe_absen,
                        'waktu_absen' => $absensi->waktu_absen->toDateTimeString(),
                        'lokasi' => $lokasi->lokasi,
                        'titik_koordinat_lokasi' => $absensi->titik_koordinat_lokasi,
                        'titik_koordinat_kamu' => $absensi->titik_koordinat_kamu,
                        'foto_wajah' => $absensi->foto_wajah,
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error saat menyimpan absensi: '.$e->getMessage());
                Log::error('Stack trace: '.$e->getTraceAsString());
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error submitAbsensi: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan absensi: '.$e->getMessage(),
            ], 500);
        } finally {
            Log::info('='.str_repeat('=', 50));
        }
    }

    /**
     * Riwayat absensi user yang login
     */
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

    /**
     * Cek status absensi hari ini
     */
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
}
