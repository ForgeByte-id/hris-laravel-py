<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

class KaryawanFaceImportService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'png', 'webp'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const PREVIEW_MAX_DIMENSION = 720;
    private const PREVIEW_WEBP_QUALITY = 72;
    private const ENCODING_JPEG_QUALITY = 90;

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
     * Store a compressed preview copy of an uploaded face image under storage/app/imports/faces.
     *
     * The database stores only the relative path. The file is served through an
     * authenticated Laravel route, not exposed through the public disk.
     */
    public function storeUploadedPreview(UploadedFile $file, int $idKaryawan): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $this->assertSupportedImage($extension, (string) $file->getMimeType());

        $relativePath = 'karyawan/' . $idKaryawan . '/face.webp';
        $destination = storage_path('app/imports/faces/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        File::ensureDirectoryExists(dirname($destination));
        $this->writeCompressedImage($file->getRealPath(), $extension, $destination, 'webp');

        return $relativePath;
    }

    /**
     * Store a compressed preview copy from a camera-captured image binary.
     */
    public function storeCameraPreview(string $imageBinary, int $idKaryawan): string
    {
        $relativePath = 'karyawan/' . $idKaryawan . '/face.webp';
        $destination = storage_path('app/imports/faces/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        File::ensureDirectoryExists(dirname($destination));
        $this->writeCompressedImageFromString($imageBinary, $destination, 'webp');

        return $relativePath;
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

    /**
     * Store a compressed preview copy from a CSV/JSON/XLSX referenced image.
     */
    public function storeImportPreview(string $faceImagePath, int $idKaryawan): string
    {
        $resolvedPath = $this->resolveImportFacePath($faceImagePath);
        $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $mimeType = File::mimeType($resolvedPath) ?: '';
        $this->assertSupportedImage($extension, $mimeType);

        $relativePath = 'karyawan/' . $idKaryawan . '/face.webp';
        $destination = storage_path('app/imports/faces/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        File::ensureDirectoryExists(dirname($destination));
        $this->writeCompressedImage($resolvedPath, $extension, $destination, 'webp');

        return $relativePath;
    }

    /**
     * Return a normalized storage/app/imports/faces relative path for DB storage.
     */
    public function storedImportPath(string $faceImagePath): string
    {
        $resolvedPath = $this->resolveImportFacePath($faceImagePath);
        $realBase = realpath(storage_path('app/imports/faces'));

        if (!$realBase) {
            throw new RuntimeException('Folder storage/app/imports/faces tidak ditemukan.');
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', ltrim(substr($resolvedPath, strlen($realBase)), DIRECTORY_SEPARATOR));
    }

    private function assertSupportedImage(string $extension, string $mimeType): void
    {
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true) || !in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new RuntimeException('File wajah harus berupa gambar JPG, PNG, atau WEBP.');
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

        $tempPath = $this->faceRecognitionService->makeTempPath('import_face_');

        try {
            $this->writeCompressedImage($sourcePath, $extension, $tempPath, 'jpg', null, self::ENCODING_JPEG_QUALITY);

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

    private function writeCompressedImage(
        string|false $sourcePath,
        string $extension,
        string $destination,
        string $format,
        ?int $maxDimension = self::PREVIEW_MAX_DIMENSION,
        ?int $quality = self::PREVIEW_WEBP_QUALITY
    ): void {
        if (!$sourcePath || !is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException('File wajah tidak dapat dibaca. Silakan upload ulang file gambar.');
        }

        $image = $this->createImageResource($sourcePath, $extension);

        try {
            $image = $this->resizeImageIfNeeded($image, $maxDimension);
            $this->saveImage($image, $destination, $format, $quality ?? self::PREVIEW_WEBP_QUALITY);
        } finally {
            \imagedestroy($image);
        }
    }

    private function writeCompressedImageFromString(
        string $imageBinary,
        string $destination,
        string $format,
        ?int $maxDimension = self::PREVIEW_MAX_DIMENSION,
        int $quality = self::PREVIEW_WEBP_QUALITY
    ): void {
        $this->assertGdFunction('imagecreatefromstring', 'membaca gambar');

        $image = \imagecreatefromstring($imageBinary);

        if (!$image) {
            throw new RuntimeException('File wajah tidak dapat dibaca. Silakan upload ulang file gambar.');
        }

        try {
            $image = $this->resizeImageIfNeeded($image, $maxDimension);
            $this->saveImage($image, $destination, $format, $quality);
        } finally {
            \imagedestroy($image);
        }
    }

    /**
     * @return \GdImage
     */
    private function createImageResource(string $sourcePath, string $extension)
    {
        $function = match ($extension) {
            'jpg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            'webp' => 'imagecreatefromwebp',
            default => false,
        };

        if (!$function) {
            throw new RuntimeException('File wajah harus berupa gambar JPG, PNG, atau WEBP.');
        }

        $this->assertGdFunction($function, strtoupper($extension));

        return $function($sourcePath)
            ?: throw new RuntimeException('File wajah tidak dapat diproses. Gunakan gambar JPG, PNG, atau WEBP yang valid.');
    }

    /**
     * @param \GdImage $image
     *
     * @return \GdImage
     */
    private function resizeImageIfNeeded($image, ?int $maxDimension)
    {
        if (!$maxDimension) {
            return $image;
        }

        $this->assertGdFunction('imagecreatetruecolor', 'kompresi gambar');
        $this->assertGdFunction('imagecopyresampled', 'kompresi gambar');

        $width = \imagesx($image);
        $height = \imagesy($image);
        $largestSide = max($width, $height);

        if ($largestSide <= $maxDimension) {
            return $image;
        }

        $scale = $maxDimension / $largestSide;
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $resized = \imagecreatetruecolor($targetWidth, $targetHeight);

        \imagealphablending($resized, false);
        \imagesavealpha($resized, true);

        if (!\imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
            \imagedestroy($resized);
            throw new RuntimeException('Gagal mengompres foto wajah.');
        }

        \imagedestroy($image);

        return $resized;
    }

    /**
     * @param \GdImage $image
     */
    private function saveImage($image, string $destination, string $format, int $quality): void
    {
        $function = match ($format) {
            'jpg' => 'imagejpeg',
            'webp' => 'imagewebp',
            default => false,
        };

        if (!$function) {
            throw new RuntimeException('Format kompresi foto wajah tidak valid.');
        }

        $this->assertGdFunction($function, strtoupper($format));

        if (!$function($image, $destination, $quality)) {
            throw new RuntimeException('Gagal menyimpan preview foto wajah.');
        }
    }

    private function assertGdFunction(string $function, string $context): void
    {
        if (!function_exists($function)) {
            throw new RuntimeException(
                "Dukungan {$context} pada ekstensi PHP GD belum aktif. Rebuild container aplikasi agar upload wajah JPG/PNG/WEBP bisa diproses."
            );
        }
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
