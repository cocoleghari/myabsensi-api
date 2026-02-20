<?php
// app/Http/Controllers/UserLokasiController.php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            
            $lokasis = Lokasi::where('user_id', $userId)
                             ->select('id', 'lokasi', 'koordinat')
                             ->orderBy('lokasi')
                             ->get();
            
            return response()->json($lokasis);
            
        } catch (\Exception $e) {
            Log::error('Error getUserLokasi: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit absensi
     */
    public function submitAbsensi(Request $request)
    {
        Log::info('SUBMIT ABSENSI: ' . json_encode($request->all()));
        
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'User tidak ditemukan'], 401);
            }
            
            $lokasiId = $request->lokasi_id;
            $titikKoordinatKamu = $request->titik_koordinat_kamu; // Terima koordinat real-time
            
            // Ambil data lokasi
            $lokasi = Lokasi::find($lokasiId);
            
            if (!$lokasi) {
                return response()->json(['message' => 'Lokasi tidak ditemukan'], 404);
            }
            
            // Cek kepemilikan
            if ($lokasi->user_id != $user->id) {
                return response()->json(['message' => 'Lokasi bukan milik anda'], 403);
            }
            
            // Cek apakah sudah absen hari ini
            $sudahAbsen = Absensi::where('user_id', $user->id)
                ->where('lokasi_id', $lokasiId)
                ->whereDate('waktu_absen', now()->toDateString())
                ->exists();

            if ($sudahAbsen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah absen hari ini'
                ], 400);
            }
            
            // Simpan absensi
            $absensi = new Absensi();
            $absensi->user_id = $user->id;
            $absensi->lokasi_id = $lokasiId;
            $absensi->titik_koordinat_lokasi = $lokasi->koordinat;
            $absensi->titik_koordinat_kamu = $titikKoordinatKamu; // Simpan koordinat real-time
            $absensi->waktu_absen = now();
            $absensi->save();
            
            Log::info('Absensi berhasil: ID=' . $absensi->id);
            Log::info('Titik koordinat lokasi: ' . $absensi->titik_koordinat_lokasi);
            Log::info('Titik koordinat kamu: ' . $absensi->titik_koordinat_kamu);
            
            return response()->json([
                'success' => true,
                'message' => 'Absensi berhasil',
                'data' => [
                    'id' => $absensi->id,
                    'waktu_absen' => $absensi->waktu_absen->toDateTimeString(),
                    'lokasi' => $lokasi->lokasi,
                    'titik_koordinat_lokasi' => $absensi->titik_koordinat_lokasi,
                    'titik_koordinat_kamu' => $absensi->titik_koordinat_kamu,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error submitAbsensi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Riwayat absensi
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
            Log::error('Error getRiwayatAbsensi: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cek status absensi hari ini
     */
    public function cekStatusHariIni(Request $request)
    {
        try {
            $userId = $request->user()->id;
            
            $absensi = Absensi::where('user_id', $userId)
                ->whereDate('waktu_absen', now()->toDateString())
                ->with('lokasi')
                ->first();
            
            return response()->json([
                'sudah_absen' => $absensi ? true : false,
                'tanggal' => now()->toDateString(),
                'data_absensi' => $absensi
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}