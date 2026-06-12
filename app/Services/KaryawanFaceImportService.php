<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

class KaryawanFaceImportService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png'];

    public function __construct(private readonly FaceRecognitionService $faceRecognitionService)
    {
    }

    /**
     * Encode a face image uploaded from a manual form.
     *
     * The uploaded image is copied into the shared face temp directory first so
     * the existing Python service contract and FaceRecognitionService::encodeFace()
     * can be reused for both Docker and local Windows runs.
     *
     * @return array<int, float|int>
     */
    public function encodeUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $this->assertSupportedImage($extension, (string) $file->getMimeType());

        return $this->encodeReadableImagePath($file->getRealPath(), $extension);
    }

    /**
     * Encode a face image referenced by an import CSV.
     *
     * CSV paths are resolved under storage/app/imports/faces to avoid allowing
     * arbitrary filesystem reads from uploaded import files.
     *
     * @return array<int, float|int>
     */
    public function encodeImportPath(string $faceImagePath): array
    {
        $resolvedPath = $this->resolveImportFacePath($faceImagePath);
        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $mimeType = File::mimeType($resolvedPath) ?: '';
        $this->assertSupportedImage($extension, $mimeType);

        return $this->encodeReadableImagePath($resolvedPath, $extension);
    }

    private function assertSupportedImage(string $extension, string $mimeType): void
    {
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true) || !in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('File wajah harus berupa gambar JPG, JPEG, atau PNG.');
        }
    }

    /**
     * @return array<int, float|int>
     */
    private function encodeReadableImagePath(string|false $sourcePath, string $extension): array
    {
        if (!$sourcePath || !is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException('File wajah tidak dapat dibaca. Silakan upload ulang file gambar.');
        }

        $tempPath = $this->makeTempPathWithExtension($extension);

        try {
            if (!copy($sourcePath, $tempPath)) {
                throw new RuntimeException('Gagal menyiapkan file wajah untuk diproses.');
            }

            $result = $this->faceRecognitionService->encodeFace($tempPath);

            if (!($result['success'] ?? false)) {
                throw new RuntimeException($this->friendlyFaceError($result['error'] ?? null));
            }

            if (empty($result['encoding']) || !is_array($result['encoding'])) {
                throw new RuntimeException('Wajah tidak terdeteksi. Gunakan foto dengan satu wajah yang jelas.');
            }

            return $result['encoding'];
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function makeTempPathWithExtension(string $extension): string
    {
        $basePath = $this->faceRecognitionService->makeTempPath('import_face_');

        return preg_replace('/\.jpg$/i', '.' . $extension, $basePath) ?: $basePath;
    }

    private function resolveImportFacePath(string $faceImagePath): string
    {
        $path = trim($faceImagePath);

        if ($path === '') {
            throw new RuntimeException('Path file wajah kosong.');
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^(storage/app/)?imports/faces/#', '', $path) ?? $path;

        if (str_contains($path, '..')) {
            throw new RuntimeException('Path file wajah tidak valid.');
        }

        $baseDir = storage_path('app/imports/faces');
        $candidate = $baseDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $realBase = realpath($baseDir);
        $realCandidate = realpath($candidate);

        if (!$realBase || !$realCandidate || !str_starts_with($realCandidate, $realBase . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('File wajah tidak ditemukan di storage/app/imports/faces.');
        }

        return $realCandidate;
    }

    private function friendlyFaceError(?string $error): string
    {
        $message = strtolower((string) $error);

        if (str_contains($message, 'no face') || str_contains($message, 'not detected')) {
            return 'Wajah tidak terdeteksi. Gunakan foto dengan wajah yang jelas dan menghadap kamera.';
        }

        if (str_contains($message, 'multiple') || str_contains($message, 'more than one')) {
            return 'Terdeteksi lebih dari satu wajah. Gunakan foto dengan satu wajah saja.';
        }

        if (str_contains($message, 'invalid image path') || str_contains($message, 'not found')) {
            return 'File wajah tidak dapat diproses. Pastikan konfigurasi FACE_TEMP_DIR dan ALLOWED_IMAGE_TMP_DIR sudah sesuai.';
        }

        return 'Gagal memproses wajah. Pastikan foto jelas dan layanan face recognition sedang berjalan.';
    }
}
