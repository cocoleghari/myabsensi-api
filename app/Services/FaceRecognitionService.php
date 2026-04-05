<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    private string $pythonServiceUrl;

    private float $threshold;

    public function __construct()
    {
        $this->pythonServiceUrl = config('services.face_recognition.url', 'http://localhost:8001');
        $this->threshold = config('services.face_recognition.threshold', 0.6);
    }

    /**
     * Bandingkan foto absen dengan foto referensi user.
     *
     * @return array{verified: bool, confidence: float, message: string}
     */
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
            $confidence = (float) ($data['confidence'] ?? 0);

            return [
                'verified' => $confidence >= $this->threshold,
                'confidence' => $confidence,
                'message' => $confidence >= $this->threshold
                    ? 'Wajah berhasil diverifikasi'
                    : 'Wajah tidak cocok (confidence: '.round($confidence * 100).'%)',
            ];

        } catch (\Exception $e) {
            Log::error('Face recognition exception', ['error' => $e->getMessage()]);

            return ['verified' => false, 'confidence' => 0, 'message' => 'Gagal menghubungi layanan verifikasi'];
        }
    }

    /**
     * Simpan foto wajah referensi user ke storage.
     */
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
            $confidence = (float) ($data['confidence'] ?? 0);

            return [
                'verified' => $confidence >= $this->threshold,
                'confidence' => $confidence,
                'message' => $confidence >= $this->threshold
                    ? 'Wajah berhasil diverifikasi'
                    : 'Wajah tidak cocok ('.round($confidence * 100).'%)',
            ];
        } catch (\Exception $e) {
            return ['verified' => false, 'confidence' => 0, 'message' => $e->getMessage()];
        }
    }
}
