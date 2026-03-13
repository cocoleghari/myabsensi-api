<?php

namespace App\Http\Controllers;

use App\Models\PusatLokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PusatLokasiController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('GET Pusat Lokasi - Start');

            $query = PusatLokasi::query();

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lokasi', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
                Log::info('Search applied: '.$search);
            }

            $sortField = $request->get('sort_field', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $allowedSortFields = ['id', 'nama_lokasi', 'created_at', 'updated_at'];
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            if ($request->has('per_page') && is_numeric($request->per_page)) {
                $data = $query->paginate($request->per_page);
                Log::info('Pagination: page '.($request->get('page', 1)).', per_page: '.$request->per_page);
            } else {
                $data = $query->get();
                Log::info('Total data: '.$data->count());
            }

            Log::info('Get pusat lokasi success');

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil diambil',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Error get pusat lokasi: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pusat lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('POST Pusat Lokasi - Start');
            Log::info('Request data:', $request->all());

            // Validasi input
            $validator = Validator::make($request->all(), [
                'nama_lokasi' => 'required|string|max:255',
                'titik_kordinat' => 'required|string|max:100',
                'keterangan' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validasi format koordinat
            $kordinat = $request->titik_kordinat;
            $parts = explode(',', $kordinat);

            if (count($parts) != 2) {
                Log::warning('Format koordinat tidak valid: '.$kordinat);

                return response()->json([
                    'success' => false,
                    'message' => 'Format koordinat tidak valid. Gunakan format: lat,lng (contoh: -6.893361,107.602376)',
                ], 422);
            }

            $lat = trim($parts[0]);
            $lng = trim($parts[1]);

            if (! is_numeric($lat) || ! is_numeric($lng)) {
                Log::warning('Koordinat bukan angka: lat='.$lat.', lng='.$lng);

                return response()->json([
                    'success' => false,
                    'message' => 'Koordinat harus berupa angka',
                ], 422);
            }

            // Simpan data
            $pusatLokasi = PusatLokasi::create([
                'nama_lokasi' => $request->nama_lokasi,
                'titik_kordinat' => $kordinat,
                'keterangan' => $request->keterangan,
            ]);

            Log::info('Pusat lokasi created', [
                'id' => $pusatLokasi->id,
                'nama' => $pusatLokasi->nama_lokasi,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil ditambahkan',
                'data' => $pusatLokasi,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store pusat lokasi: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan data pusat lokasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            Log::info('GET Pusat Lokasi Detail - ID: '.$id);

            $pusatLokasi = PusatLokasi::find($id);

            if (! $pusatLokasi) {
                Log::warning('Pusat lokasi not found: '.$id);

                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail pusat lokasi',
                'data' => $pusatLokasi,
            ]);

        } catch (\Exception $e) {
            Log::error('Error show pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pusat lokasi',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('PUT Pusat Lokasi - ID: '.$id);
            Log::info('Request data:', $request->all());

            $pusatLokasi = PusatLokasi::find($id);

            if (! $pusatLokasi) {
                Log::warning('Pusat lokasi not found: '.$id);

                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            // Validasi input
            $validator = Validator::make($request->all(), [
                'nama_lokasi' => 'sometimes|required|string|max:255',
                'titik_kordinat' => 'sometimes|required|string|max:100',
                'keterangan' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validasi format koordinat jika diupdate
            if ($request->has('titik_kordinat')) {
                $kordinat = $request->titik_kordinat;
                $parts = explode(',', $kordinat);

                if (count($parts) != 2) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format koordinat tidak valid. Gunakan format: lat,lng',
                    ], 422);
                }

                $lat = trim($parts[0]);
                $lng = trim($parts[1]);

                if (! is_numeric($lat) || ! is_numeric($lng)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Koordinat harus berupa angka',
                    ], 422);
                }
            }

            // Update data
            $pusatLokasi->update($request->only(['nama_lokasi', 'titik_kordinat', 'keterangan']));

            Log::info('Pusat lokasi updated', ['id' => $pusatLokasi->id]);

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil diupdate',
                'data' => $pusatLokasi,
            ]);

        } catch (\Exception $e) {
            Log::error('Error update pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data pusat lokasi',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Log::info(' DELETE Pusat Lokasi - ID: '.$id);

            $pusatLokasi = PusatLokasi::find($id);

            if (! $pusatLokasi) {
                Log::warning('⚠️ Pusat lokasi not found: '.$id);

                return response()->json([
                    'success' => false,
                    'message' => 'Data pusat lokasi tidak ditemukan',
                ], 404);
            }

            // TODO: Jika ada relasi dengan tabel lain, cek di sini
            // Contoh: cek apakah lokasi ini sedang digunakan oleh user

            $pusatLokasi->delete();

            Log::info('Pusat lokasi deleted', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Data pusat lokasi berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            Log::error('Error delete pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pusat lokasi',
            ], 500);
        }
    }

    public function destroyMultiple(Request $request)
    {
        try {
            Log::info('DELETE Multiple Pusat Lokasi');
            Log::info('Request ids:', $request->all());

            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|integer|exists:pusat_lokasis,id',
            ]);

            if ($validator->fails()) {
                Log::warning('Validasi gagal:', $validator->errors()->toArray());

                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $count = PusatLokasi::whereIn('id', $request->ids)->delete();

            Log::info('Multiple pusat lokasi deleted', [
                'ids' => $request->ids,
                'count' => $count,
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$count} data pusat lokasi berhasil dihapus",
            ]);

        } catch (\Exception $e) {
            Log::error('Error delete multiple pusat lokasi: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pusat lokasi',
            ], 500);
        }
    }
}

// namespace App\Http\Controllers;

// use App\Models\PusatLokasi;
// use Illuminate\Http\Request;

// class PusatLokasiController extends Controller
// {
//     public function index()
//     {
//         $data = PusatLokasi::all();

//         return response()->json([
//             'success' => true,
//             'data' => $data,
//         ]);
//     }

//     public function store(Request $request)
//     {
//         $pusatLokasi = PusatLokasi::create([
//             'nama_lokasi' => $request->nama_lokasi,
//             'titik_kordinat' => $request->titik_kordinat,
//             'keterangan' => $request->keterangan,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Data berhasil ditambahkan',
//             'data' => $pusatLokasi,
//         ]);
//     }

//     public function show($id)
//     {
//         $pusatLokasi = PusatLokasi::find($id);

//         return response()->json([
//             'success' => true,
//             'data' => $pusatLokasi,
//         ]);
//     }

//     public function update(Request $request, $id)
//     {
//         $pusatLokasi = PusatLokasi::find($id);

//         $pusatLokasi->update([
//             'nama_lokasi' => $request->nama_lokasi,
//             'titik_kordinat' => $request->titik_kordinat,
//             'keterangan' => $request->keterangan,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Data berhasil diupdate',
//             'data' => $pusatLokasi,
//         ]);
//     }

//     public function destroy($id)
//     {
//         $pusatLokasi = PusatLokasi::find($id);
//         $pusatLokasi->delete();

//         return response()->json([
//             'success' => true,
//             'message' => 'Data berhasil dihapus',
//         ]);
//     }

//     public function destroyMultiple(Request $request)
//     {
//         PusatLokasi::whereIn('id', $request->ids)->delete();

//         return response()->json([
//             'success' => true,
//             'message' => 'Data berhasil dihapus',
//         ]);
//     }
// }
