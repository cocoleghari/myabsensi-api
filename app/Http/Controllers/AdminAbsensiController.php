<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminAbsensiController extends Controller
{
    // =========================================================================
    // GET ALL ABSENSI
    // =========================================================================

    public function getAllAbsensi(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $query = Absensi::with([
                'employee:id,full_name,nickname,employee_code,nik',
                'pusatLokasi:id,nama_lokasi,titik_kordinat',
                'shift:id,nama,kode',
            ]);

            // Filter per karyawan
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filter per tanggal
            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal_absen', $request->tanggal);
            }

            // Filter per bulan & tahun
            if ($request->filled('bulan') && $request->filled('tahun')) {
                $query->whereMonth('tanggal_absen', $request->bulan)
                    ->whereYear('tanggal_absen', $request->tahun);
            }

            // Filter per status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $absensis = $query->orderBy('tanggal_absen', 'desc')
                ->orderBy('waktu_absen', 'desc')
                ->get();

            Log::info('Admin getAllAbsensi - Total: '.$absensis->count());

            return response()->json([
                'success' => true,
                'data' => $absensis,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getAllAbsensi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data absensi',
            ], 500);
        }
    }

    // =========================================================================
    // GET ALL EMPLOYEES (dropdown untuk filter)
    // =========================================================================

    public function getAllEmployees(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $employees = Employee::select('id', 'full_name', 'nickname', 'employee_code', 'nik')
                ->orderBy('full_name')
                ->get();

            Log::info('Admin getAllEmployees - Total: '.$employees->count());

            return response()->json([
                'success' => true,
                'data' => $employees,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getAllEmployees: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data karyawan',
            ], 500);
        }
    }

    // =========================================================================
    // DELETE ABSENSI
    // =========================================================================

    public function deleteAbsensi(int $id)
    {
        try {
            if (auth()->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $absensi = Absensi::find($id);

            if (! $absensi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data absensi tidak ditemukan',
                ], 404);
            }

            // Hapus foto dari storage jika ada
            if ($absensi->foto_absen_path) {
                try {
                    // foto_absen_path menyimpan nama file saja (misal: masuk_john_1234.jpg)
                    $storagePath = 'public/foto_absensi/'.$absensi->foto_absen_path;

                    if (Storage::exists($storagePath)) {
                        Storage::delete($storagePath);
                        Log::info('Foto absensi dihapus: '.$storagePath);
                    } else {
                        Log::warning('Foto tidak ditemukan di storage: '.$storagePath);
                    }
                } catch (\Exception $e) {
                    Log::error('Error saat menghapus foto absensi: '.$e->getMessage());
                }
            }

            $absensi->delete();

            Log::info('Absensi deleted', [
                'id' => $id,
                'employee_id' => $absensi->employee_id,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data absensi berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleteAbsensi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data absensi: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    public function getStatistics(Request $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $today = now()->toDateString();

            $statistics = [
                'total_employees' => Employee::count(),
                'total_absensi' => Absensi::count(),
                'absensi_hari_ini' => Absensi::whereDate('tanggal_absen', $today)->count(),
                'absensi_masuk_hari_ini' => Absensi::whereDate('tanggal_absen', $today)
                    ->where('tipe_absen', 'masuk')
                    ->count(),
                'absensi_pulang_hari_ini' => Absensi::whereDate('tanggal_absen', $today)
                    ->where('tipe_absen', 'pulang')
                    ->count(),
                'terlambat_hari_ini' => Absensi::whereDate('tanggal_absen', $today)
                    ->where('status', 'terlambat')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getStatistics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik',
            ], 500);
        }
    }
}
