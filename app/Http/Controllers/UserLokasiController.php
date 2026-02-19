<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserLokasiController extends Controller
{
    /**
     * Ambil lokasi untuk user yang sedang login
     */
    public function getUserLokasi(Request $request)
    {
        try {
            $userId = $request->user()->id;
            
            Log::info("Fetching lokasi untuk user ID: $userId");
            
            $lokasis = Lokasi::where('user_id', $userId)
                             ->select('id', 'lokasi', 'koordinat', 'created_at')
                             ->orderBy('lokasi')
                             ->get();
            
            return response()->json($lokasis);
            
        } catch (\Exception $e) {
            Log::error('Error getUserLokasi: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit absensi
     */
    public function submitAbsensi(Request $request)
    {
        // Logging lengkap untuk debugging
        Log::info('=' . str_repeat('=', 50));
        Log::info('SUBMIT ABSENSI DIPANGGIL');
        Log::info('Request method: ' . $request->method());
        Log::info('Request headers: ' . json_encode($request->headers->all()));
        Log::info('Request all data: ' . json_encode($request->all()));
        Log::info('Request input: ' . json_encode($request->input()));
        Log::info('Content type: ' . $request->header('Content-Type'));
        
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'lokasi_id' => 'required|integer|exists:lokasis,id',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal:', $validator->errors()->toArray());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            
            if (!$user) {
                Log::error('User tidak ditemukan dari token');
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 401);
            }
            
            $userId = $user->id;
            $lokasiId = $request->lokasi_id;

            Log::info("User ID: $userId, Lokasi ID: $lokasiId");
            Log::info("User role: " . $user->role);

            // Cek apakah user memiliki role user
            if ($user->role !== 'user') {
                Log::warning("User $userId bukan role user (role: {$user->role})");
                
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya user yang dapat melakukan absensi'
                ], 403);
            }

            // Cek apakah lokasi ini memang milik user yang login
            $lokasi = Lokasi::where('id', $lokasiId)
                            ->where('user_id', $userId)
                            ->first();

            if (!$lokasi) {
                Log::warning("Lokasi $lokasiId tidak valid untuk user $userId");
                
                // Cek apakah lokasi ada (mungkin milik user lain)
                $lokasiExists = Lokasi::find($lokasiId);
                if ($lokasiExists) {
                    Log::warning("Lokasi $lokasiId milik user {$lokasiExists->user_id}, bukan $userId");
                } else {
                    Log::warning("Lokasi $lokasiId tidak ditemukan di database");
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi tidak valid atau bukan milik anda'
                ], 403);
            }

            // Cek apakah sudah absen hari ini
            $sudahAbsen = Absensi::where('user_id', $userId)
                ->where('lokasi_id', $lokasiId)
                ->whereDate('waktu_absen', now()->toDateString())
                ->exists();

            if ($sudahAbsen) {
                Log::warning("User $userId sudah absen hari ini di lokasi $lokasiId");
                
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absensi hari ini'
                ], 400);
            }

            // Simpan absensi dengan DB transaction untuk keamanan
            DB::beginTransaction();
            
            try {
                $absensi = Absensi::create([
                    'user_id' => $userId,
                    'lokasi_id' => $lokasiId,
                    'waktu_absen' => now(),
                ]);

                Log::info("Absensi berhasil dibuat dengan ID: " . $absensi->id);

                // Load relasi lokasi
                $absensi->load('lokasi');

                DB::commit();

                Log::info("Transaction committed successfully");

                return response()->json([
                    'success' => true,
                    'message' => 'Absensi berhasil',
                    'data' => [
                        'id' => $absensi->id,
                        'waktu_absen' => $absensi->waktu_absen->toDateTimeString(),
                        'lokasi' => $absensi->lokasi->lokasi,
                        'koordinat' => $absensi->lokasi->koordinat,
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error saat menyimpan absensi: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error submitAbsensi: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan absensi: ' . $e->getMessage()
            ], 500);
        } finally {
            Log::info('=' . str_repeat('=', 50));
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
                               ->with('lokasi:id,lokasi,koordinat')
                               ->orderBy('waktu_absen', 'desc')
                               ->get();
            
            return response()->json($absensis);
            
        } catch (\Exception $e) {
            Log::error('Error getRiwayatAbsensi: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat absensi'
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
            
            $sudahAbsen = Absensi::where('user_id', $userId)
                ->whereDate('waktu_absen', now()->toDateString())
                ->exists();
            
            return response()->json([
                'success' => true,
                'sudah_absen' => $sudahAbsen,
                'tanggal' => now()->toDateString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error cekStatusHariIni: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal cek status'
            ], 500);
        }
    }
}