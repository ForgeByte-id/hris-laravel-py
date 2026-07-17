<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FaceRecognitionService;
use App\Services\KaryawanFaceImportService;
use App\Services\KaryawanImportService;
use RuntimeException;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function index()
    {
        $karyawan = Karyawan::with(['jabatan', 'divisi'])->get();
        return view('employees.karyawan_index', compact('karyawan'));
    }

    public function create()
    {
        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();
        $divisiList  = Divisi::orderBy('nama_divisi')->get();
        $shiftList = Shift::orderBy('nama_shift')->get();
        return view('employees.karyawan_create', compact('jabatanList', 'divisiList', 'shiftList'));
    }

    public function importForm()
    {
        return view('employees.import', [
            'summary' => session('import_summary'),
        ]);
    }

    public function importEmployees(Request $request, KaryawanImportService $importService)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,json,xlsx|max:2048',
        ], [
            'import_file.required' => 'Upload file import terlebih dahulu.',
            'import_file.mimes' => 'File import harus berformat CSV, JSON, atau XLSX.',
            'import_file.max' => 'Ukuran file import maksimal 2 MB.',
        ]);

        try {
            $summary = $importService->importFile($request->file('import_file'));

            return redirect()->route('karyawan.import')
                ->with('import_summary', $summary)
                ->with('success', "Import selesai: {$summary['success']} sukses, {$summary['updated']} updated, {$summary['skipped']} skipped, {$summary['failed']} failed.");
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Import gagal diproses. Pastikan format CSV sesuai template.');
        }
    }

    public function importTemplate()
    {
        $headers = [
            'nama',
            'username',
            'email',
            'password',
            'nama_divisi',
            'nama_jabatan',
            'tanggal_masuk',
            'status_aktif',
            'status_karyawan',
            'face_image_path',
        ];

        $example = [
            'Budi Santoso',
            'budi.santoso',
            'budi@hris.local',
            '',
            'Operasional',
            'Staff',
            now()->toDateString(),
            'Aktif',
            'Tetap',
            'budi.jpg',
        ];

        $csv = implode(',', $headers) . "\n" . implode(',', $example) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template-import-karyawan.csv"',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            // Karyawan fields
            'nama'                  => 'required|string|max:255',
            'id_jabatan'            => 'nullable|exists:jabatans,id',
            'id_divisi'             => 'nullable|exists:divisis,id',
            'tanggal_masuk'         => 'nullable|date',
            'status_aktif'          => 'nullable|in:Aktif,Nonaktif',
            'status_karyawan'       => 'nullable|in:Tetap,Kontrak,Training',
            // New user account fields
            'username'              => 'required|string|max:255|unique:users,username',
            'email'                 => 'nullable|email|max:255|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            'role'                  => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create the login account first
            $user = User::create([
                'username' => $request->username,
                'email'    => $request->email ?: null,
                'password' => $request->password,   // hashed automatically by User model cast
                'role'     => $request->role,
            ]);

            // Assign Spatie role based on jabatan -> role mapping
            $jabatan = \App\Models\Jabatan::find($request->id_jabatan);
            $roleName = app(\App\Services\AuthorizationService::class)->roleForJabatan($jabatan?->nama_jabatan ?? '');
            $user->assignRole($roleName);

            // 2. Create karyawan linked to the new user
            $statusKaryawan      = $request->status_karyawan ?: 'Tetap';
            $yearlyLeaveQuota    = $request->status_karyawan ?? ($statusKaryawan === 'Tetap' ? 12 : 0);
            $remainingLeaveQuota = $request->status_karyawan ?? $yearlyLeaveQuota;

            Karyawan::create([
                'nama'                  => $request->nama,
                'id_jabatan'            => $request->id_jabatan,
                'id_divisi'             => $request->id_divisi,
                'tanggal_masuk'         => $request->tanggal_masuk,
                'status_aktif'          => $request->status_aktif ?: 'Aktif',
                'status_karyawan'       => $statusKaryawan,
                'id_user'               => $user->id_user,
            ]);

            DB::commit();

            return redirect()->route('karyawan.index')
                ->with('success', "Karyawan '{$request->nama}' dan akun login berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Gagal membuat karyawan: ' . $e->getMessage());
        }
    }

    public function show($id_karyawan, \App\Services\LeaveQuotaService $leaveQuotaService)
    {
        $karyawan = Karyawan::with(['jabatan', 'divisi', 'absensi', 'cuti'])->findOrFail($id_karyawan);
        $leaveBalances = $leaveQuotaService->ensureBalancesFor($karyawan);
        return view('employees.karyawan_show', compact('karyawan', 'leaveBalances'));
    }

    public function faceImage($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);

        abort_if(empty($karyawan->face_image_path), 404, 'Foto wajah belum tersedia.');

        $baseDir = storage_path('app/imports/faces');
        $relativePath = str_replace(['\\', '..'], ['/', ''], $karyawan->face_image_path);
        $candidate = $baseDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        $realBase = realpath($baseDir);
        $realCandidate = realpath($candidate);

        abort_unless(
            $realBase && $realCandidate && str_starts_with($realCandidate, $realBase . DIRECTORY_SEPARATOR),
            404,
            'Foto wajah tidak ditemukan.'
        );

        return response()->file($realCandidate);
    }

    public function edit($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        $jabatanList = Jabatan::orderBy('nama_jabatan')->get();
        $divisiList = Divisi::orderBy('nama_divisi')->get();
        $shiftList = Shift::orderBy('nama_shift')->get();
        //$userList = User::whereDoesntHave('karyawan')
        //    ->orWhere('id_user', $karyawan->id_user)
        //    ->get();
        return view('employees.karyawan_edit', compact('karyawan', 'jabatanList', 'divisiList', 'shiftList'));
    }

    public function update(Request $request, $id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);

        $request->validate([
            'nama'          => 'required|string|max:255',
            'id_jabatan'    => 'required|exists:jabatans,id',
            'id_divisi'     => 'required|exists:divisis,id',
            'tanggal_masuk' => 'required|date',
            'status_aktif' => 'required|in:Aktif,Nonaktif',
            'status_karyawan' => 'required|in:Tetap,Kontrak,Training',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')
                    ->ignore($karyawan->id_user, 'id_user')
            ],
            'role'           => 'required|in:Admin,Atasan,Karyawan',
        ]);

        //$statusKaryawan = $request->status_karyawan ?: ($karyawan->status_karyawan ?: 'Tetap');
        //$yearlyLeaveQuota = $request->status_karyawan ?? $karyawan->status_karyawan ?? ($statusKaryawan === 'Tetap' ? 12 : 0);
        //$remainingLeaveQuota = $request->status_karyawan ?? $karyawan->status_karyawan ?? $yearlyLeaveQuota;
        DB::beginTransaction();

        try {
        $karyawan->update([
            'nama'          => $request->nama,
            'id_jabatan'    => $request->id_jabatan,
            'id_divisi'     => $request->id_divisi,
            'tanggal_masuk' => $request->tanggal_masuk,
            'status_aktif' => $request->status_aktif,
            'status_karyawan' => $request->status_karyawan,
            
        ]);

        $karyawan->user->update([
            'username' => $request->username,
            'role'     => $request->role
        ]);

        // Assign Spatie role based on jabatan -> role mapping
        $jabatan = \App\Models\Jabatan::find($request->id_jabatan);
        $roleName = app(\App\Services\AuthorizationService::class)->roleForJabatan($jabatan?->nama_jabatan ?? '');
        $karyawan->user->syncRoles([$roleName]);

        DB::commit();
        
        return redirect()->route('karyawan.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
        } catch (\Throwable $e) {
        Log::error('Gagal memperbarui data karyawan', [
            'id_karyawan' => $id_karyawan,
            'id_user' => $karyawan->id_user,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return back()
            ->withInput()
            ->with(
                'error',
                'Gagal memperbarui data karyawan: '.$e->getMessage()
            );
        }
    }

    public function destroy($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        $karyawan->delete();

        return redirect()->route('karyawan.index')
            ->with('success', 'Karyawan berhasil dihapus.');
    }

    public function registerFace($id_karyawan)
    {
        $karyawan = Karyawan::findOrFail($id_karyawan);
        return view('karyawan.register-face', compact('karyawan'));
    }

    public function importFaceForm(Request $request)
    {
        $karyawanList = Karyawan::with(['jabatan', 'divisi'])->orderBy('nama')->get();
        $selectedKaryawan = $request->filled('id_karyawan')
            ? Karyawan::find($request->id_karyawan)
            : null;

        return view('karyawan.import-face', compact('karyawanList', 'selectedKaryawan'));
    }

    public function importFace(Request $request, KaryawanFaceImportService $faceImportService)
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'face_image' => [
                'required',
                'file',
                'mimes:jpg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());

                    if (!in_array($extension, ['jpg', 'png', 'webp'], true)) {
                        $fail('File wajah harus berekstensi .jpg, .png, atau .webp.');
                    }
                },
            ],
        ], [
            'id_karyawan.required' => 'Pilih karyawan terlebih dahulu.',
            'face_image.required' => 'Upload file wajah terlebih dahulu.',
            'face_image.mimes' => 'File wajah harus berupa JPG, PNG, atau WEBP.',
            'face_image.mimetypes' => 'File wajah harus berupa gambar JPG, PNG, atau WEBP yang valid.',
            'face_image.max' => 'Ukuran file wajah maksimal 2 MB.',
        ]);

        try {
            $karyawan = Karyawan::findOrFail($request->id_karyawan);
            $encoding = $faceImportService->encodeUploadedFile($request->file('face_image'));
            $previewPath = $faceImportService->storeUploadedPreview($request->file('face_image'), $karyawan->id_karyawan);
            $this->persistFaceEncoding($karyawan, $encoding, $previewPath);

            return redirect()->route('karyawan.index')
                ->with('success', "Wajah {$karyawan->nama} berhasil diimport.");
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()
                ->with('error', 'Gagal import wajah. Pastikan file valid dan layanan face recognition berjalan.');
        }
    }

    public function storeFaceEncoding(
        Request $request,
        FaceRecognitionService $faceRecognitionService,
        KaryawanFaceImportService $faceImportService
    )
    {
        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'photo' => 'required|string',
        ]);

        try {
            $imageData = $request->photo;
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageBinary = base64_decode($imageData);

            // Simpan foto sementara di direktori yang bisa diakses Python service
            $tempPath = $faceRecognitionService->makeTempPath('temp_face_');
            file_put_contents($tempPath, $imageBinary);

            // Kirim ke Python service untuk generate encoding
            $result = $faceRecognitionService->encodeFace($tempPath);

            // Hapus file temporary
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if (!$result['success']) {
                $statusCode = !empty($result['service_error']) ? 503 : 400;
                $message = !empty($result['service_error'])
                    ? 'Layanan pengenalan wajah sedang tidak tersedia. Coba beberapa saat lagi.'
                    : ($result['error'] ?? 'Gagal deteksi wajah. Pastikan wajah jelas.');

                return response()->json([
                    'success' => false,
                    'code' => !empty($result['service_error']) ? 'FACE_SERVICE_UNAVAILABLE' : 'FACE_ENCODING_FAILED',
                    'message' => $message,
                ], $statusCode);
            }

            if (!isset($result['encoding'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada wajah terdeteksi.'
                ], 400);
            }

            $karyawan = Karyawan::find($request->id_karyawan);
            $previewPath = $faceImportService->storeCameraPreview($imageBinary, $karyawan->id_karyawan);
            $this->persistFaceEncoding($karyawan, $result['encoding'], $previewPath);

            return response()->json([
                'success' => true,
                'message' => 'Wajah berhasil didaftarkan!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server. Silakan coba lagi.'
            ], 500);
        }
    }

    public function deleteFaceEncoding($id_karyawan)
    {
        $karyawan = Karyawan::find($id_karyawan);
        
        if ($karyawan) {
            $karyawan->face_embedding = null;
            $karyawan->face_image_path = null;
            $karyawan->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Data wajah berhasil dihapus.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan.'
        ], 404);
    }

    /**
     * Persist face encoding generated by camera capture or manual image import.
     *
     * @param array<int, float|int> $encoding
     */
    private function persistFaceEncoding(Karyawan $karyawan, array $encoding, ?string $faceImagePath = null): void
    {
        $karyawan->face_embedding = json_encode($encoding);
        $karyawan->face_image_path = $faceImagePath;
        $karyawan->save();
    }
}
