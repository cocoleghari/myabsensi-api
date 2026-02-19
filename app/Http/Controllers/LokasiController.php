<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index()
    {
        try {
            $lokasis = Lokasi::with('user:id,name')->get();
            return response()->json($lokasis);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'lokasi' => 'required|string',
                'koordinat' => 'required|string',
            ]);

            $lokasi = Lokasi::create([
                'user_id' => $request->user_id,
                'lokasi' => $request->lokasi,
                'koordinat' => $request->koordinat,
            ]);

            return response()->json($lokasi->load('user:id,name'), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $lokasi = Lokasi::findOrFail($id);
            
            $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'lokasi' => 'sometimes|string',
                'koordinat' => 'sometimes|string',
            ]);

            $lokasi->update($request->all());
            
            return response()->json($lokasi->load('user:id,name'));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $lokasi = Lokasi::findOrFail($id);
            $lokasi->delete();
            
            return response()->json(['message' => 'Lokasi berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}