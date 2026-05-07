<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    private string $serviceUrl;
    private int $timeout = 30;

    public function __construct()
    {
        // Strip any accidental trailing slash so URLs like "http://face-service:5000/"
        // don't produce double-slash paths (e.g. "http://face-service:5000//api/encode-face").
        $this->serviceUrl = rtrim(
            config('services.face_recognition.url', 'http://localhost:5000'),
            '/'
        );
    }

    /**
     * Return a unique temp file path for a face image.
     *
     * Directory resolution order:
     *   1. FACE_TEMP_DIR env var        — explicit override in .env
     *   2. storage_path('app/temp')     — project storage dir (default)
     *
     * Without Docker (local dev):
     *   Leave FACE_TEMP_DIR unset in Laravel .env.
     *   PHP resolves to {project}/storage/app/temp.
     *   Set ALLOWED_IMAGE_TMP_DIR to the same absolute path in
     *   face_recognition_service/.env so Python can find the file.
     *
     * With Docker:
     *   FACE_TEMP_DIR is set to /var/www/html/storage/app/temp via docker-compose.yml.
     *   storage_path() resolves to the same path inside the container anyway,
     *   so the env var is just an explicit safety net.
     *   Python's ALLOWED_IMAGE_TMP_DIR is set to the same path in docker-compose.yml.
     *   Both containers mount ./storage at /var/www/html/storage, so the
     *   file written by PHP is immediately visible to Python.
     */
    public function makeTempPath(string $prefix = 'face_'): string
    {
        $dir = rtrim((string) env('FACE_TEMP_DIR', storage_path('app/temp')), '/\\');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $prefix . uniqid() . '.jpg';
    }

    /**
     * Register a face for an employee
     * Accepts base64 encoded image or file path
     *
     * @param string $imagePath Path to the image file
     * @return array ['success' => bool, 'encoding' => array, 'error' => string|null]
     */
    public function encodeFace(string $imagePath): array
    {
        try {
            if (!file_exists($imagePath)) {
                return [
                    'success' => false,
                    'error' => 'Image file not found',
                ];
            }

            Log::info("Encoding face from: {$imagePath}");

            $response = Http::timeout($this->timeout)->post(
                "{$this->serviceUrl}/api/encode-face",
                [
                    'image_path' => $imagePath,
                ]
            );

            if (!$response->successful()) {
                $error = $response->json('error')
                      ?? $response->json('message')
                      ?? substr($response->body(), 0, 300)
                      ?? 'Unknown error';
                Log::warning('Face encoding failed', [
                    'endpoint'    => "{$this->serviceUrl}/api/encode-face",
                    'http_status' => $response->status(),
                    'error'       => $error,
                    'image_path'  => $imagePath,
                ]);
                return ['success' => false, 'error' => $error];
            }

            $data = $response->json();

            return [
                'success' => true,
                'encoding' => $data['encoding'],
                'face_location' => $data['face_location'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("Face encoding exception: {$e->getMessage()}");
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recognize/match a face against registered employees
     *
     * @param string $imagePath Path to the image file
     * @return array ['matched' => bool, 'id_karyawan' => int|null, 'confidence' => float, 'error' => string|null]
     */
    public function recognizeFace(string $imagePath): array
    {
        try {
            if (!file_exists($imagePath)) {
                return [
                    'matched' => false,
                    'id_karyawan' => null,
                    'confidence' => 0,
                    'error' => 'Image file not found',
                ];
            }

            Log::info("Recognizing face from: {$imagePath}");

            $response = Http::timeout($this->timeout)->post(
                "{$this->serviceUrl}/api/recognize-face",
                [
                    'image_path' => $imagePath,
                ]
            );

            if (!$response->successful()) {
                $error = $response->json('error')
                      ?? $response->json('message')
                      ?? substr($response->body(), 0, 300)
                      ?? 'Unknown error';
                Log::warning('Face recognition failed', [
                    'endpoint'    => "{$this->serviceUrl}/api/recognize-face",
                    'http_status' => $response->status(),
                    'error'       => $error,
                    'image_path'  => $imagePath,
                ]);
                return ['matched' => false, 'id_karyawan' => null, 'confidence' => 0, 'error' => $error];
            }

            $data = $response->json();

            if ($data['matched'] === false) {
                Log::info("Face not recognized. Best distance: {$data['best_distance']}");
                return [
                    'matched' => false,
                    'id_karyawan' => null,
                    'confidence' => 0,
                    'error' => 'Face not recognized',
                ];
            }

            return [
                'matched' => true,
                'id_karyawan' => $data['id_karyawan'],
                'confidence' => $data['confidence'],
                'distance' => $data['distance'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("Face recognition exception: {$e->getMessage()}");
            return [
                'matched' => false,
                'id_karyawan' => null,
                'confidence' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Health check - verify service is running
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->serviceUrl}/api/health");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Face recognition service health check failed: {$e->getMessage()}");
            return false;
        }
    }
}
