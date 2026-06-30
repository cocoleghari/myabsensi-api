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
    }

    public function verify(string $fotoAbsenPath, string $fotoReferensiPath): array
    {
        return $this->callVerifyEndpoint(
            storage_path('app/'.$fotoAbsenPath),
            storage_path('app/'.$fotoReferensiPath),
            timeout: 15
        );
    }

    public function verifyByPath(string $absolutePathAbsen, string $absolutePathReferensi): array
    {
        return $this->callVerifyEndpoint($absolutePathAbsen, $absolutePathReferensi, timeout: 60);
    }

    private function callVerifyEndpoint(string $pathAbsen, string $pathReferensi, int $timeout): array
    {
        try {
            $response = Http::timeout($timeout)
                ->attach('foto_absen', file_get_contents($pathAbsen), 'absen.jpg')
                ->attach('foto_referensi', file_get_contents($pathReferensi), 'referensi.jpg')
                ->post("{$this->pythonServiceUrl}/verify");

            // ── Kasus 422: wajah tidak terdeteksi sama sekali ──
            // PENTING: ini beda dengan "tidak cocok". Jangan ditampilkan sebagai
            // "Wajah tidak cocok (0%)" karena bisa menyesatkan user — masalahnya
            // bukan wajah beda orang, tapi foto tidak bisa diproses.
            if ($response->status() === 422) {
                $errorBody = $response->json('detail', []);
                $errorType = $errorBody['error_type'] ?? 'unknown';

                Log::warning('Face recognition: wajah tidak terdeteksi', [
                    'error_type' => $errorType,
                    'message' => $errorBody['message'] ?? null,
                    'detector_used' => $errorBody['detector_used'] ?? null,
                ]);

                return [
                    'verified' => false,
                    'confidence' => 0,
                    'message' => 'Wajah tidak terdeteksi dengan jelas pada salah satu foto. Pastikan pencahayaan cukup dan wajah terlihat penuh, lalu coba foto ulang.',
                    'error_type' => 'face_not_detected',
                ];
            }

            // ── Kasus error lain (500, timeout, dst) ──
            if ($response->failed()) {
                Log::error('Face recognition service error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'verified' => false,
                    'confidence' => 0,
                    'message' => 'Layanan verifikasi tidak tersedia, silakan coba lagi',
                    'error_type' => 'service_error',
                ];
            }

            // ── Sukses: parse hasil dari DeepFace ──
            $data = $response->json();
            $verified = (bool) ($data['verified'] ?? false);
            $confidence = (float) ($data['confidence'] ?? 0);
            $distance = $data['distance'] ?? null;
            $rawConfidence = $data['raw_confidence'] ?? null;
            $threshold = $data['threshold'] ?? null;
            $detectorUsed = $data['detector_used'] ?? null;

            // Log semua detail mentah — ini yang dipakai untuk debugging kasus
            // "0% padahal wajah sama". Kalau raw_confidence jauh negatif (misal -1.5),
            // berarti distance dari DeepFace memang sangat tinggi, bukan cuma
            // "sedikit di bawah threshold".
            Log::info('Face recognition result', [
                'verified' => $verified,
                'confidence' => $confidence,
                'raw_confidence' => $rawConfidence,
                'distance' => $distance,
                'model_threshold' => $threshold,
                'detector_used' => $detectorUsed,
            ]);

            return [
                'verified' => $verified,
                'confidence' => $confidence,
                'message' => $verified
                    ? 'Wajah berhasil diverifikasi'
                    : 'Wajah tidak cocok ('.round($confidence * 100).'%)',
                'error_type' => null,
                // field tambahan, opsional dipakai di frontend/debugging
                'distance' => $distance,
                'detector_used' => $detectorUsed,
            ];

        } catch (\Exception $e) {
            Log::error('Face recognition exception', ['error' => $e->getMessage()]);

            return [
                'verified' => false,
                'confidence' => 0,
                'message' => 'Gagal menghubungi layanan verifikasi',
                'error_type' => 'connection_error',
            ];
        }
    }

    public function saveFotoReferensi(UploadedFile $foto, int $userId): string
    {
        return $foto->storeAs('wajah_referensi', "user_{$userId}.jpg", 'local');
    }
}
