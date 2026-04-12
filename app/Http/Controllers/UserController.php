<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->select([
                'id', 'name', 'email', 'role', 'nik',
                'nama_stempel', 'jabatan', 'kantor',
                'jk', 'nomor_telp', 'tgl_masuk', 'photo_url',
            ])
            ->where('role', 'user');

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('nik', 'like', $search)
                    ->orWhere('jabatan', 'like', $search)
                    ->orWhere('kantor', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('jabatan')) {
            $query->whereIn('jabatan', (array) $request->jabatan);
        }

        // ← Filter kantor (baru)
        if ($request->filled('kantor')) {
            $query->whereIn('kantor', (array) $request->kantor);
        }

        $perPage = $request->get('per_page', 20);
        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Data karyawan berhasil diambil',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    // Jabatan list (tidak berubah)
    public function jabatanList()
    {
        $jabatanList = User::where('role', 'user')
            ->whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->distinct()
            ->orderBy('jabatan')
            ->pluck('jabatan');

        return response()->json(['status' => true, 'data' => $jabatanList]);
    }

    // ← Endpoint baru: daftar kantor unik
    public function kantorList()
    {
        $kantorList = User::where('role', 'user')
            ->whereNotNull('kantor')
            ->where('kantor', '!=', '')
            ->distinct()
            ->orderBy('kantor')
            ->pluck('kantor');

        return response()->json(['status' => true, 'data' => $kantorList]);
    }

    public function show($id)
    {
        $user = User::where('id', $id)
            ->where('role', 'user')
            ->select([
                'id', 'name', 'email', 'role', 'nik',
                'jabatan', 'kantor', 'jk', 'nomor_telp',
                'tgl_lahir', 'tgl_masuk', 'alamat', 'photo_url',
            ])
            ->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        return response()->json(['status' => true, 'data' => $user]);
    }
}
