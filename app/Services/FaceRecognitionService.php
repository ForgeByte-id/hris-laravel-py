<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    /**
     * Base URL of the Python face recognition service.
     *
     * Docker:
     *   http://face-service:5000
     *
     * Local/non-Docker:
     *   http://localhost:5050
     */
    private string $serviceUrl;

    /**
     * Default request timeout in seconds.
     */
    private int $timeout = 30;

    /**
     * Create a new FaceRecognitionService instance.
     */
    public function __construct()
    {
        $this->serviceUrl = rtrim(
            (string) config('services.face_recognition.url', 'http://face-service:5000'),
            '/'
        );
    }

    /**
     * Build a Laravel HTTP client for the face recognition service.
     *
     * The curl proxy options are intentionally disabled because internal Docker
     * service calls such as "http://face-service:5000" must not be routed
     * through HTTP_PROXY / HTTPS_PROXY. Without this, the request may return
     * an empty 502 Bad Gateway response.
     */
    private function faceHttp(?int $timeout = null): PendingRequest
    {
        return Http::timeout($timeout ?? $this->timeout)
            ->acceptJson()
            ->asJson()
            ->withOptions([
                'curl' => [
                    CURLOPT_PROXY => '',
                    CURLOPT_NOPROXY => '*',
                ],
            ]);
    }

    /**
     * Build a full endpoint URL for the Python service.
     */
    private function endpoint(string $path): string
    {
        return $this->serviceUrl . '/' . ltrim($path, '/');
    }

    /**
     * Return a readable error message from an HTTP response.
     */
    private function responseError(Response $response): string
    {
        return $response->json('error')
            ?? $response->json('message')
            ?? trim(substr($response->body(), 0, 300))
            ?: 'Unknown error';
    }

    /**
     * Check whether an image path exists before sending it to Python.
     *
     * @return string|null Error message when invalid, otherwise null.
     */
    private function validateImagePath(string $imagePath): ?string
    {
        if (!file_exists($imagePath)) {
            return 'Image file not found';
        }

        if (!is_file($imagePath)) {
            return 'Image path is not a file';
        }

        if (!is_readable($imagePath)) {
            return 'Image file is not readable';
        }

        return null;
    }

    /**
     * Create a unique temporary file path for a face image.
     *
     * Directory resolution order:
     * 1. FACE_TEMP_DIR env var.
     * 2. Laravel storage_path('app/temp').
     *
     * In Docker, FACE_TEMP_DIR should point to a shared volume path that is
     * mounted by both Laravel and the Python face-service container.
     */
    public function makeTempPath(string $prefix = 'face_'): string
    {
        $dir = rtrim((string) env('FACE_TEMP_DIR', storage_path('app/temp')), '/\\');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $prefix . uniqid('', true) . '.jpg';
    }

    /**
     * Encode/register a face image using the Python face recognition service.
     *
     * @param string $imagePath Absolute image path visible to both Laravel and Python.
     *
     * @return array{
     *     success: bool,
     *     encoding?: array,
     *     face_location?: mixed,
     *     error?: string|null,
     *     service_error?: bool
     * }
     */
    public function encodeFace(string $imagePath): array
    {
        if ($error = $this->validateImagePath($imagePath)) {
            return [
                'success' => false,
                'error' => $error,
            ];
        }

        $endpoint = $this->endpoint('/api/encode-face');

        try {
            Log::info('Encoding face image.', [
                'endpoint' => $endpoint,
                'image_path' => $imagePath,
            ]);

            $response = $this->faceHttp()->post($endpoint, [
                'image_path' => $imagePath,
            ]);

            if (!$response->successful()) {
                $error = $this->responseError($response);

                Log::warning('Face encoding failed.', [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'error' => $error,
                    'image_path' => $imagePath,
                ]);

                return [
                    'success' => false,
                    'error' => $error,
                    'service_error' => true,
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'encoding' => $data['encoding'] ?? [],
                'face_location' => $data['face_location'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Face encoding exception.', [
                'endpoint' => $endpoint,
                'image_path' => $imagePath,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'service_error' => true,
            ];
        }
    }

    /**
     * Recognize a face image against registered employee face embeddings.
     *
     * @param string $imagePath Absolute image path visible to both Laravel and Python.
     *
     * @return array{
     *     matched: bool,
     *     id_karyawan: int|null,
     *     confidence: float|int,
     *     distance?: float|int|null,
     *     error?: string|null
     * }
     */
    public function recognizeFace(string $imagePath): array
    {
        if ($error = $this->validateImagePath($imagePath)) {
            return $this->recognitionFailure($error);
        }

        $endpoint = $this->endpoint('/api/recognize-face');

        try {
            Log::info('Recognizing face image.', [
                'endpoint' => $endpoint,
                'image_path' => $imagePath,
            ]);

            $response = $this->faceHttp()->post($endpoint, [
                'image_path' => $imagePath,
            ]);

            if (!$response->successful()) {
                $error = $this->responseError($response);

                Log::warning('Face recognition failed.', [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'error' => $error,
                    'image_path' => $imagePath,
                ]);

                return $this->recognitionFailure($error, true);
            }

            $data = $response->json();

            if (($data['matched'] ?? false) === false) {
                Log::info('Face not recognized.', [
                    'endpoint' => $endpoint,
                    'best_distance' => $data['best_distance'] ?? null,
                    'image_path' => $imagePath,
                ]);

                return $this->recognitionFailure('Face not recognized');
            }

            return [
                'matched' => true,
                'id_karyawan' => $data['id_karyawan'] ?? null,
                'confidence' => $data['confidence'] ?? 0,
                'distance' => $data['distance'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Face recognition exception.', [
                'endpoint' => $endpoint,
                'image_path' => $imagePath,
                'message' => $e->getMessage(),
            ]);

            return $this->recognitionFailure($e->getMessage(), true);
        }
    }

    /**
     * Check whether the Python face recognition service is reachable.
     */
    public function healthCheck(): bool
    {
        $endpoint = $this->endpoint('/api/health');

        try {
            $response = $this->faceHttp(timeout: 5)->get($endpoint);

            if (!$response->successful()) {
                Log::warning('Face recognition service health check failed.', [
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Face recognition service health check exception.', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Standard failed recognition payload.
     *
     * @return array{
     *     matched: false,
     *     id_karyawan: null,
     *     confidence: int,
     *     error: string,
     *     service_error?: bool
     * }
     */
    private function recognitionFailure(string $error, bool $serviceError = false): array
    {
        return [
            'matched' => false,
            'id_karyawan' => null,
            'confidence' => 0,
            'error' => $error,
            'service_error' => $serviceError,
        ];
    }
}
