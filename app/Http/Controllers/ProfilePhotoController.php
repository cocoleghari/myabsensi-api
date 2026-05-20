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
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan',
            ], 404);
        }

        // Hapus foto lama jika ada
        if ($employee->photo_url) {
            $oldPath = str_replace(
                Storage::disk('public')->url(''),
                '',
                $employee->photo_url
            );
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Nama file dari nickname/full_name karyawan
        $namaStempel = $employee->nickname ?? $employee->full_name ?? 'user';
        $namaFile = strtolower($namaStempel);
        $namaFile = preg_replace('/\s+/', '_', $namaFile);
        $namaFile = preg_replace('/[^a-z0-9_]/', '', $namaFile);
        $namaFile = trim($namaFile, '_');
        $namaFile = $namaFile ?: 'emp_'.$employee->id;
        $namaFile = $namaFile.'_'.time().'.jpg';

        $path = 'photos/profile/'.$namaFile;

        Storage::disk('public')->put(
            $path,
            file_get_contents($request->file('photo')->getRealPath())
        );

        $url = Storage::disk('public')->url($path);

        // Simpan ke employees, bukan users
        $employee->update(['photo_url' => $url]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui',
            'photo_url' => $url,
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($employee?->photo_url) {
            $oldPath = str_replace('/storage/', 'public/', parse_url($employee->photo_url, PHP_URL_PATH));
            Storage::delete($oldPath);
            $employee->update(['photo_url' => null]);
        }

        return response()->json([
            'message' => 'Foto profil berhasil dihapus',
        ]);
    }
}
