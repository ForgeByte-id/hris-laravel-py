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
        $this->serviceUrl = config('services.face_recognition.url', 'http://localhost:5000');
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
                $error = $response->json('error', 'Unknown error');
                Log::warning("Face encoding failed: {$error}");
                return [
                    'success' => false,
                    'error' => $error,
                ];
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
                $error = $response->json('error', 'Unknown error');
                Log::warning("Face recognition failed: {$error}");
                return [
                    'matched' => false,
                    'id_karyawan' => null,
                    'confidence' => 0,
                    'error' => $error,
                ];
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
