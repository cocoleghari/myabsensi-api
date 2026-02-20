<?php
// app/Http/Controllers/AdminAbsensiController.php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * Get all users for filter (hanya user dengan role 'user')
     */
    public function getAllUsers(Request $request)
    {
        try {
            // Pastikan user adalah admin
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Ambil semua user dengan role 'user'
            $users = User::select('id', 'name', 'email')
                        ->where('role', 'user')
                        ->orderBy('name')
                        ->get();

            Log::info('Total users ditemukan: ' . $users->count());

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
}