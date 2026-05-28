<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    private string $pythonServiceUrl;

    public function __construct()
    {
        $this->pythonServiceUrl = config('services.face_recognition.url', 'http://localhost:8001');
        // threshold dihapus — keputusan verified sepenuhnya dari DeepFace/Python
    }

    public function verify(string $fotoAbsenPath, string $fotoReferensiPath): array
    {
        try {
            $response = Http::timeout(15)
                ->attach('foto_absen', file_get_contents(storage_path('app/'.$fotoAbsenPath)), 'absen.jpg')
                ->attach('foto_referensi', file_get_contents(storage_path('app/'.$fotoReferensiPath)), 'referensi.jpg')
                ->post("{$this->pythonServiceUrl}/verify");

            if ($response->failed()) {
                Log::error('Face recognition service error', ['status' => $response->status(), 'body' => $response->body()]);

                return ['verified' => false, 'confidence' => 0, 'message' => 'Layanan verifikasi tidak tersedia'];
            }

            $data = $response->json();
            $verified = (bool) ($data['verified'] ?? false);      // ← dari DeepFace
            $confidence = (float) ($data['confidence'] ?? 0);

            Log::info('Face recognition result', [
                'verified' => $verified,
                'confidence' => $confidence,
                'distance' => $data['distance'] ?? null,
                'threshold' => $data['threshold'] ?? null,
            ]);

            return [
                'verified' => $verified,
                'confidence' => $confidence,
                'message' => $verified
                    ? 'Wajah berhasil diverifikasi'
                    : 'Wajah tidak cocok ('.round($confidence * 100).'%)',
            ];

        } catch (\Exception $e) {
            Log::error('Face recognition exception', ['error' => $e->getMessage()]);

            return ['verified' => false, 'confidence' => 0, 'message' => 'Gagal menghubungi layanan verifikasi'];
        }
    }

    public function saveFotoReferensi(UploadedFile $foto, int $userId): string
    {
        return $foto->storeAs('wajah_referensi', "user_{$userId}.jpg", 'local');
    }

    public function verifyByPath(string $absolutePathAbsen, string $absolutePathReferensi): array
    {
        try {
            $response = Http::timeout(60)
                ->attach('foto_absen', file_get_contents($absolutePathAbsen), 'absen.jpg')
                ->attach('foto_referensi', file_get_contents($absolutePathReferensi), 'referensi.jpg')
                ->post("{$this->pythonServiceUrl}/verify");

            if ($response->failed()) {
                return ['verified' => false, 'confidence' => 0, 'message' => 'Service error'];
            }

            $data = $response->json();
            $verified = (bool) ($data['verified'] ?? false);      // ← dari DeepFace
            $confidence = (float) ($data['confidence'] ?? 0);

            return [
                'verified' => $verified,
                'confidence' => $confidence,
                'message' => $verified
                    ? 'Wajah berhasil diverifikasi'
                    : 'Wajah tidak cocok ('.round($confidence * 100).'%)',
            ];
        } catch (\Exception $e) {
            return ['verified' => false, 'confidence' => 0, 'message' => $e->getMessage()];
        }
    }
}
