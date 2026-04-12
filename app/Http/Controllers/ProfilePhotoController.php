<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    // Upload / ganti foto
    public function upload(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $request->user();

        // Hapus foto lama jika ada
        if ($user->photo_url) {
            $oldPath = str_replace(
                Storage::disk('public')->url(''),
                '',
                $user->photo_url
            );
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Buat nama file dari nama_stempel
        $namaStempel = $user->nama_stempel ?? $user->name ?? 'user';

        // Sanitasi: huruf kecil, spasi → underscore, hapus karakter aneh
        $namaFile = strtolower($namaStempel);
        $namaFile = preg_replace('/\s+/', '_', $namaFile);          // spasi → _
        $namaFile = preg_replace('/[^a-z0-9_]/', '', $namaFile);    // hapus karakter selain huruf, angka, _
        $namaFile = trim($namaFile, '_');                            // hapus _ di awal/akhir
        $namaFile = $namaFile ?: 'user_'.$user->id;               // fallback jika kosong

        // Tambah timestamp agar unik jika upload ulang
        $namaFile = $namaFile.'_'.time().'.jpg';

        $path = 'photos/profile/'.$namaFile;

        // Simpan dengan nama custom
        Storage::disk('public')->put(
            $path,
            file_get_contents($request->file('photo')->getRealPath())
        );

        $url = Storage::disk('public')->url($path);
        $user->update(['photo_url' => $url]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui',
            'photo_url' => $url,
        ]);
    }

    // Hapus foto
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->photo_url) {
            $oldPath = str_replace('/storage/', 'public/', parse_url($user->photo_url, PHP_URL_PATH));
            Storage::delete($oldPath);
            $user->update(['photo_url' => null]);
        }

        return response()->json([
            'message' => 'Foto profil berhasil dihapus',
        ]);
    }
}
