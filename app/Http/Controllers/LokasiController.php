<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LokasiController extends Controller
{
    /**
     * GET /api/lokasi
     * Mengambil semua data lokasi dengan relasi user
     */
    public function index()
    {
        try {
            $lokasis = Lokasi::with('user:id,name')->get();

            Log::info('Get all lokasi', ['total' => $lokasis->count()]);

            return response()->json($lokasis);
        } catch (\Exception $e) {
            Log::error('Error get lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/lokasi
     * Menyimpan lokasi baru dengan validasi duplikat koordinat
     */
    public function store(Request $request)
    {
        try {
            Log::info('='.str_repeat('=', 50));
            Log::info('STORE LOKASI - START');
            Log::info('Request data:', $request->all());

            // Validasi input
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'lokasi' => 'required|string|max:255',
                'koordinat' => 'required|string|max:100',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // CEK DUPLIKAT TITIK KOORDINAT UNTUK USER YANG SAMA
            $existingLokasi = Lokasi::where('user_id', $request->user_id)
                ->where('koordinat', $request->koordinat)
                ->first();

            if ($existingLokasi) {
                Log::warning('Duplikat koordinat ditemukan', [
                    'user_id' => $request->user_id,
                    'koordinat' => $request->koordinat,
                    'existing_id' => $existingLokasi->id,
                    'existing_lokasi' => $existingLokasi->lokasi,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'User ini sudah memiliki lokasi dengan titik koordinat yang sama',
                    'existing_data' => [
                        'id' => $existingLokasi->id,
                        'lokasi' => $existingLokasi->lokasi,
                        'koordinat' => $existingLokasi->koordinat,
                        'created_at' => $existingLokasi->created_at,
                    ],
                ], 422); // 422 Unprocessable Entity
            }

            // Simpan lokasi baru
            $lokasi = Lokasi::create([
                'user_id' => $request->user_id,
                'lokasi' => $request->lokasi,
                'koordinat' => $request->koordinat,
            ]);

            Log::info('Lokasi berhasil dibuat:', [
                'id' => $lokasi->id,
                'user_id' => $lokasi->user_id,
                'lokasi' => $lokasi->lokasi,
                'koordinat' => $lokasi->koordinat,
            ]);

            return response()->json(
                $lokasi->load('user:id,name'),
                201
            );

        } catch (\Exception $e) {
            Log::error('Error store lokasi: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('UPDATE LOKASI - ID: '.$id);
            Log::info('Request data:', $request->all());

            $lokasi = Lokasi::findOrFail($id);

            // Validasi input
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|exists:users,id',
                'lokasi' => 'sometimes|string|max:255',
                'koordinat' => 'sometimes|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Jika koordinat diupdate, cek duplikat
            if ($request->has('koordinat') && $request->koordinat != $lokasi->koordinat) {
                $userId = $request->get('user_id', $lokasi->user_id);

                $existingLokasi = Lokasi::where('user_id', $userId)
                    ->where('koordinat', $request->koordinat)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingLokasi) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User ini sudah memiliki lokasi lain dengan titik koordinat yang sama',
                        'existing_data' => [
                            'id' => $existingLokasi->id,
                            'lokasi' => $existingLokasi->lokasi,
                            'koordinat' => $existingLokasi->koordinat,
                        ],
                    ], 422);
                }
            }

            // Update data
            $lokasi->update($request->all());

            Log::info('Lokasi updated:', ['id' => $id]);

            return response()->json($lokasi->load('user:id,name'));

        } catch (\Exception $e) {
            Log::error('Error update lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate lokasi',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $lokasi = Lokasi::findOrFail($id);
            $lokasi->delete();

            Log::info('Lokasi deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Error delete lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus lokasi',
            ], 500);
        }
    }

    public function users()
    {
        try {
            $users = User::where('role', 'user')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            return response()->json($users);

        } catch (\Exception $e) {
            Log::error('Error get users for lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data users',
            ], 500);
        }
    }

    public function cekDuplikat(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'koordinat' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $exists = Lokasi::where('user_id', $request->user_id)
                ->where('koordinat', $request->koordinat)
                ->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Koordinat sudah ada' : 'Koordinat tersedia',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal cek duplikat',
            ], 500);
        }
    }
}
