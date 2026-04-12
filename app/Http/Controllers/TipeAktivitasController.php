<?php

namespace App\Http\Controllers;

use App\Models\TipeAktivitas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipeAktivitasController extends Controller
{
    // GET /tipe-aktivitas
    public function index(): JsonResponse
    {
        $data = TipeAktivitas::orderBy('nama')->get();

        return response()->json([
            'status' => true,
            'message' => 'Data tipe aktivitas berhasil diambil',
            'data' => $data,
        ]);
    }

    // POST /admin/tipe-aktivitas
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama' => 'required|string|unique:tipe_aktivitas,nama',
            'has_tujuan' => 'boolean',
            'has_kendaraan' => 'boolean',
        ]);

        $tipe = TipeAktivitas::create([
            'nama' => $request->nama,
            'has_tujuan' => $request->has_tujuan ?? false,
            'has_kendaraan' => $request->has_kendaraan ?? false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tipe aktivitas berhasil ditambahkan',
            'data' => $tipe,
        ], 201);
    }

    // PUT /admin/tipe-aktivitas/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $tipe = TipeAktivitas::find($id);

        if (! $tipe) {
            return response()->json([
                'status' => false,
                'message' => 'Tipe aktivitas tidak ditemukan',
            ], 404);
        }

        $request->validate([
            'nama' => 'required|string|unique:tipe_aktivitas,nama,'.$id,
            'has_tujuan' => 'boolean',
            'has_kendaraan' => 'boolean',
        ]);

        $tipe->update([
            'nama' => $request->nama,
            'has_tujuan' => $request->has_tujuan ?? $tipe->has_tujuan,
            'has_kendaraan' => $request->has_kendaraan ?? $tipe->has_kendaraan,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tipe aktivitas berhasil diperbarui',
            'data' => $tipe,
        ]);
    }

    // DELETE /admin/tipe-aktivitas/{id}
    public function destroy(int $id): JsonResponse
    {
        $tipe = TipeAktivitas::find($id);

        if (! $tipe) {
            return response()->json([
                'status' => false,
                'message' => 'Tipe aktivitas tidak ditemukan',
            ], 404);
        }

        // Cek apakah masih dipakai
        if ($tipe->aktivitas()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tipe aktivitas tidak dapat dihapus karena masih digunakan',
            ], 422);
        }

        $tipe->delete();

        return response()->json([
            'status' => true,
            'message' => 'Tipe aktivitas berhasil dihapus',
        ]);
    }
}
