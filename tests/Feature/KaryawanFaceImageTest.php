<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\User;
use App\Services\KaryawanFaceImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class KaryawanFaceImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_employee_face_thumbnail(): void
    {
        $user = User::create([
            'username' => 'face_viewer',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $relativePath = 'karyawan/test-face.jpg';
        $absolutePath = storage_path('app/imports/faces/' . $relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));
        file_put_contents($absolutePath, base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2w=='));

        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Face Employee',
            'tanggal_masuk' => now()->toDateString(),
            'face_embedding' => json_encode([0.1, 0.2]),
            'face_image_path' => $relativePath,
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.face-image', $karyawan->id_karyawan));

        $response->assertOk();
    }

    public function test_uploaded_face_preview_is_compressed_and_stored_as_webp(): void
    {
        $this->skipIfWebpIsUnavailable();

        $sourcePath = $this->createNoisyImage('manual-face.jpg', 'jpg');
        $originalSize = filesize($sourcePath);
        $file = new UploadedFile($sourcePath, 'manual-face.jpg', 'image/jpeg', null, true);

        File::deleteDirectory(storage_path('app/imports/faces/karyawan/987654'));

        $relativePath = app(KaryawanFaceImportService::class)->storeUploadedPreview($file, 987654);
        $storedPath = storage_path('app/imports/faces/' . $relativePath);

        $this->assertSame('karyawan/987654/face.webp', $relativePath);
        $this->assertFileExists($storedPath);
        $this->assertSame('image/webp', File::mimeType($storedPath));
        $this->assertLessThan($originalSize, filesize($storedPath));
    }

    public function test_webp_face_preview_upload_is_supported(): void
    {
        $this->skipIfWebpIsUnavailable();

        $sourcePath = $this->createNoisyImage('manual-face.webp', 'webp');
        $file = new UploadedFile($sourcePath, 'manual-face.webp', 'image/webp', null, true);

        File::deleteDirectory(storage_path('app/imports/faces/karyawan/987655'));

        $relativePath = app(KaryawanFaceImportService::class)->storeUploadedPreview($file, 987655);
        $storedPath = storage_path('app/imports/faces/' . $relativePath);

        $this->assertSame('karyawan/987655/face.webp', $relativePath);
        $this->assertFileExists($storedPath);
        $this->assertSame('image/webp', File::mimeType($storedPath));
    }

    public function test_manual_face_import_rejects_unsupported_image_type(): void
    {
        $user = User::create([
            'username' => 'face_admin',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Reject Face Type',
            'tanggal_masuk' => now()->toDateString(),
        ]);
        $gif = UploadedFile::fake()->createWithContent(
            'face.gif',
            base64_decode('R0lGODlhAQABAAAAACwAAAAAAQABAAA=')
        );

        $response = $this->actingAs($user)->from(route('karyawan.import-face'))->post(route('karyawan.import-face.store'), [
            'id_karyawan' => $karyawan->id_karyawan,
            'face_image' => $gif,
        ]);

        $response->assertRedirect(route('karyawan.import-face'));
        $response->assertSessionHasErrors('face_image');
    }

    public function test_manual_face_import_rejects_jpeg_extension(): void
    {
        $user = User::create([
            'username' => 'face_admin_jpeg',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Reject Jpeg Extension',
            'tanggal_masuk' => now()->toDateString(),
        ]);
        $sourcePath = $this->createNoisyImage('manual-face.jpeg', 'jpg');
        $file = new UploadedFile($sourcePath, 'manual-face.jpeg', 'image/jpeg', null, true);

        $response = $this->actingAs($user)->from(route('karyawan.import-face'))->post(route('karyawan.import-face.store'), [
            'id_karyawan' => $karyawan->id_karyawan,
            'face_image' => $file,
        ]);

        $response->assertRedirect(route('karyawan.import-face'));
        $response->assertSessionHasErrors('face_image');
    }

    private function skipIfWebpIsUnavailable(): void
    {
        if (!function_exists('imagewebp') || !function_exists('imagecreatefromwebp')) {
            $this->markTestSkipped('GD WebP support is required for compressed face previews.');
        }
    }

    private function createNoisyImage(string $filename, string $format): string
    {
        $directory = storage_path('framework/testing/face-images');
        File::ensureDirectoryExists($directory);

        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $image = imagecreatetruecolor(960, 720);

        for ($x = 0; $x < 960; $x++) {
            for ($y = 0; $y < 720; $y++) {
                $red = ($x * 17 + $y * 3) % 256;
                $green = ($x * 5 + $y * 19) % 256;
                $blue = ($x * 11 + $y * 7) % 256;

                imagesetpixel($image, $x, $y, ($red << 16) | ($green << 8) | $blue);
            }
        }

        if ($format === 'webp') {
            imagewebp($image, $path, 90);
        } else {
            imagejpeg($image, $path, 100);
        }

        imagedestroy($image);

        return $path;
    }
}
