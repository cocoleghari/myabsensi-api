<?php
// app/Http/Controllers/AdminAbsensiController.php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminAbsensiController extends Controller
{
    /**
     * Get all absensi for admin
     */
    public function getAllAbsensi(Request $request)
    {
        try {
            // Pastikan user adalah admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $query = Absensi::with(['user', 'lokasi']);

            // Filter by user (opsional)
            if ($request->has('user_id') && $request->user_id && $request->user_id !== '') {
                $query->where('user_id', $request->user_id);
            }

            $absensis = $query->orderBy('waktu_absen', 'desc')->get();

            Log::info('Admin getAllAbsensi - Total: ' . $absensis->count());

            return response()->json([
                'success' => true,
                'data' => $absensis
            ]);

        } catch (\Exception $e) {
            Log::error('Error getAllAbsensi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data absensi'
            ], 500);
        }
    }

    /**
     * Get all users for filter
     */
    public function getAllUsers(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $users = User::select('id', 'name', 'email')
                        ->where('role', 'user')
                        ->orderBy('name')
                        ->get();

            Log::info('Admin getAllUsers - Total: ' . $users->count());

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Error getAllUsers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data users'
            ], 500);
        }
    }

    /**
     * Delete absensi by ID (admin only)
     */
    public function deleteAbsensi($id)
    {
        try {
            // Pastikan user adalah admin
            if (auth()->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $absensi = Absensi::find($id);

            if (!$absensi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data absensi tidak ditemukan'
                ], 404);
            }

            // Hapus file foto jika ada
            if ($absensi->foto_wajah) {
                try {
                    // Ambil path dari URL
                    // Contoh URL: http://10.0.2.2:8000/storage/foto_absensi/nama_file.jpg
                    // Path di storage: public/foto_absensi/nama_file.jpg
                    
                    $url = $absensi->foto_wajah;
                    
                    // Ekstrak nama file dari URL
                    $pathParts = explode('/storage/foto_absensi/', $url);
                    if (count($pathParts) > 1) {
                        $fileName = $pathParts[1];
                        $storagePath = 'public/foto_absensi/' . $fileName;
                        
                        // Cek apakah file ada di storage
                        if (Storage::exists($storagePath)) {
                            Storage::delete($storagePath);
                            Log::info('Foto absensi dihapus: ' . $storagePath);
                        } else {
                            Log::warning('Foto tidak ditemukan di storage: ' . $storagePath);
                        }
                    } else {
                        Log::warning('Format URL foto tidak sesuai: ' . $url);
                    }
                } catch (\Exception $e) {
                    Log::error('Error saat menghapus foto: ' . $e->getMessage());
                    // Tetap lanjutkan hapus data meskipun foto gagal dihapus
                }
            }

            // Hapus data absensi
            $absensi->delete();

            Log::info('Absensi deleted:', [
                'id' => $id, 
                'user_id' => $absensi->user_id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data absensi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleteAbsensi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get absensi statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $today = now()->toDateString();
            
            $statistics = [
                'total_users' => User::where('role', 'user')->count(),
                'total_absensi' => Absensi::count(),
                'absensi_hari_ini' => Absensi::whereDate('waktu_absen', $today)->count(),
                'absensi_masuk_hari_ini' => Absensi::whereDate('waktu_absen', $today)
                    ->where('tipe_absen', 'masuk')
                    ->count(),
                'absensi_pulang_hari_ini' => Absensi::whereDate('waktu_absen', $today)
                    ->where('tipe_absen', 'pulang')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error getStatistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik'
            ], 500);
        }
    }
}