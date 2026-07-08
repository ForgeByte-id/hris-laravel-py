<?php

namespace App\Http\Controllers;

use App\Services\FaceRecognitionService;
use App\Services\AttendanceService;
use App\Services\AuthorizationService;
use App\Models\Karyawan;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected FaceRecognitionService $faceRecognitionService;
    protected AttendanceService $attendanceService;
    protected AuthorizationService $authorizationService;

    public function __construct(
        FaceRecognitionService $faceRecognitionService,
        AttendanceService $attendanceService,
        AuthorizationService $authorizationService
    ) {
        $this->faceRecognitionService = $faceRecognitionService;
        $this->attendanceService = $attendanceService;
        $this->authorizationService = $authorizationService;
    }

    /**
     * Abort with 403 if the authenticated user is not an admin.
     * Used as a defence-in-depth guard on top of route middleware.
     */
    private function authorizeAdmin(): void
    {
        if (!$this->authorizationService->canManageAttendance(Auth::user())) {
            abort(403, 'Unauthorized: Admin access required');
        }
    }

    /**
     * Show the attendance check-in page (camera interface)
     * Admin only.
     */
    public function index(): View
    {
        $this->authorizeAdmin();

        $serviceHealthy = $this->faceRecognitionService->healthCheck();
        $karyawanList   = Karyawan::with(['jabatan'])->orderBy('nama')->get();

        return view('absensi.attendance_index', [
            'serviceHealthy' => $serviceHealthy,
            'karyawanList'   => $karyawanList,
        ]);
    }

    /**
     * Show attendance history
     * Admin only.
     */
    public function history(Request $request): View
    {
        $this->authorizeAdmin();
        $query = Absensi::with('karyawan');

        if ($request->has('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

        if ($request->has('id_karyawan')) {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        $absensi = $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->paginate(20);

        $karyawanList = Karyawan::all();

        return view('absensi.attendance_history', compact('absensi', 'karyawanList'));
    }

    /**
     * Process face recognition and attendance (auto-detect masuk/pulang)
     * Admin only — attendance actions are centralised through the admin interface.
     *
     * System-driven: Backend determines whether it's clock-in or clock-out
     * based on current attendance state, not user input
     */
    public function checkIn(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'photo' => 'required|string',
            'attendance_action' => 'nullable|in:masuk,pulang',
        ]);

        try {
            if (!Karyawan::whereNotNull('face_embedding')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada data wajah terdaftar. Silakan registrasi wajah karyawan terlebih dahulu.'
                ], 422);
            }

            // Decode base64 image
            $imageData = $request->photo;
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageBinary = base64_decode($imageData);

            // Save temporary image — path resolved via FACE_TEMP_DIR env var
            $tempPath = $this->faceRecognitionService->makeTempPath('temp_attendance_');
            file_put_contents($tempPath, $imageBinary);

            // Recognize the face
            $recognitionResult = $this->faceRecognitionService->recognizeFace($tempPath);

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if (!$recognitionResult['matched']) {
                return response()->json([
                    'success' => false,
                    'message' => $recognitionResult['error'] ?? 'Wajah tidak dikenali. Daftar dulu.'
                ], 422);
            }

            $idKaryawan = $recognitionResult['id_karyawan'];
            $karyawan = Karyawan::find($idKaryawan);

            if (!$karyawan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan.'
                ], 404);
            }

            if (empty($karyawan->face_embedding)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wajah karyawan belum terdaftar. Silakan lakukan registrasi wajah.'
                ], 422);
            }

            $preferredAction = match ($request->input('attendance_action')) {
                'masuk' => 'clock_in',
                'pulang' => 'clock_out',
                default => null,
            };

            // Auto-detect and process attendance, with optional user-selected intent
            $result = $this->attendanceService->processAutoAttendance($idKaryawan, $preferredAction);

            // Determine time field based on action
            $timeField = null;
            if ($result['action'] === 'clock_in') {
                $timeField = $result['attendance']?->jam_masuk;
            } elseif ($result['action'] === 'clock_out') {
                $timeField = $result['attendance']?->jam_pulang;
            }

            return response()->json([
                'success' => $result['success'],
                'action' => $result['action'],
                'message' => $result['message'],
                'data' => [
                    'nama' => $karyawan->nama,
                    'waktu' => $timeField,
                    'status' => $result['status'],
                    'menit_terlambat' => $result['attendance']?->menit_terlambat,
                    'confidence' => round($recognitionResult['confidence'], 2),
                ]
            ], $result['success'] ? 200 : 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Attendance checkIn error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's attendance summary (for dashboard/API)
     * Admin only.
     */
    public function todaysSummary(): JsonResponse
    {
        try {
            $this->authorizeAdmin();

            $summary = $this->attendanceService->getTodaysSummary();
            return response()->json($summary);
        } catch (\Exception $e) {
            Log::error("Get todays summary error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get employee's current attendance status (for dashboard)
     * Admin only.
     */
    public function getCurrentStatus($idKaryawan): JsonResponse
    {
        try {
            $this->authorizeAdmin();

            $karyawan = Karyawan::find($idKaryawan);
            if (!$karyawan) {
                return response()->json(['error' => 'Employee not found'], 404);
            }

            $attendance = Absensi::where('id_karyawan', $idKaryawan)
                ->whereDate('tanggal', Carbon::today())
                ->first();

            $clockOutAvailability = $this->attendanceService->getClockOutAvailability((int) $idKaryawan);

            return response()->json([
                'employee' => $karyawan->nama,
                'clock_in' => $attendance?->jam_masuk,
                'clock_out' => $attendance?->jam_pulang,
                'status' => $attendance?->status,
                'menit_terlambat' => $attendance?->menit_terlambat,
                'date' => $attendance?->tanggal,
                'can_clock_out' => $clockOutAvailability['can_clock_out'],
                'clock_out_available_at' => $clockOutAvailability['available_at']?->format('H:i:s'),
                'clock_out_reason' => $clockOutAvailability['reason'],
            ]);
        } catch (\Exception $e) {
            Log::error("Get current status error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent attendance records for ALL employees — admin dashboard widget.
     * Admin only.
     *
     * GET /api/attendance/recent-all?days=7
     * Returns a flat array ordered by date desc, then employee name.
     */
    public function recentAll(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $days = max(1, min((int) $request->query('days', 7), 30));

        $records = Absensi::with('karyawan')
            ->where('tanggal', '>=', Carbon::today()->subDays($days - 1)->toDateString())
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_karyawan')
            ->get()
            ->map(fn($a) => [
                'tanggal'      => $a->tanggal->format('Y-m-d'),
                'nama'         => $a->karyawan?->nama ?? '-',
                'jam_masuk'    => $a->jam_masuk,
                'jam_pulang'   => $a->jam_pulang,
                'status'       => $a->status,
                'status_label' => $a->status_label,
            ]);

        return response()->json($records);
    }

    /**
     * Get employee's attendance history
     * Admin only.
     */
    public function getHistory($idKaryawan): JsonResponse
    {
        try {
            $this->authorizeAdmin();

            $karyawan = Karyawan::find($idKaryawan);

            if (!$karyawan) {
                return response()->json(['error' => 'Employee not found'], 404);
            }

            $history = $this->attendanceService->getAttendanceHistory($idKaryawan);

            return response()->json([
                'employee' => $karyawan->nama,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            Log::error("Get history error: {$e->getMessage()}");

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a captured face photo against a specific employee.
     * Admin only — used during the attendance recording flow.
     *
     * POST /api/attendance/verify-face
     * Body: { id_karyawan: int, photo: string (base64) }
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'photo'       => 'required|string',
        ]);

        try {
            $imageData   = str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $request->photo);
            $imageBinary = base64_decode($imageData);

            $tempPath = $this->faceRecognitionService->makeTempPath('temp_verify_');
            file_put_contents($tempPath, $imageBinary);

            $result = $this->faceRecognitionService->recognizeFace($tempPath);

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            $selectedId = (int) $request->id_karyawan;

            if ($result['matched'] && (int) $result['id_karyawan'] === $selectedId) {
                return response()->json([
                    'verified'    => true,
                    'confidence'  => round($result['confidence'], 2),
                    'id_karyawan' => $result['id_karyawan'],
                ]);
            }

            // Face matched but to a DIFFERENT employee — security alert
            if ($result['matched'] && (int) $result['id_karyawan'] !== $selectedId) {
                return response()->json([
                    'verified'   => false,
                    'mismatch'   => true,
                    'confidence' => round($result['confidence'] ?? 0, 2),
                    'message'    => 'Wajah terdeteksi milik karyawan lain. Verifikasi gagal.',
                ]);
            }

            return response()->json([
                'verified'   => false,
                'mismatch'   => false,
                'confidence' => 0,
                'message'    => $result['error'] ?? 'Wajah tidak dapat dikenali.',
            ]);
        } catch (\Exception $e) {
            Log::error("verifyFace error: {$e->getMessage()}");
            return response()->json(['verified' => false, 'message' => 'Terjadi kesalahan verifikasi.'], 500);
        }
    }

    /**
     * Admin records an attendance entry.
     *
     * POST /api/attendance/admin-record
     * Body: { id_karyawan, photo?, status, jam_masuk? }
     */
    public function adminRecord(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'id_karyawan' => 'required|exists:karyawan,id_karyawan',
            'photo'       => 'nullable|string',
            'status'      => 'required|in:hadir,terlambat,remote,tidak_hadir',
            'jam_masuk'   => 'nullable|date_format:H:i',
        ]);

        try {
            $faceVerified    = false;
            $faceConfidence  = null;
            $photoHash       = null;
            $idKaryawan      = (int) $request->id_karyawan;
            $status          = $request->status;

            // Face verification — required for hadir / terlambat / remote
            if ($request->photo && $status !== 'tidak_hadir') {
                $imageData   = str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $request->photo);
                $imageBinary = base64_decode($imageData);
                $photoHash   = hash('sha256', $imageBinary);

                $tempPath = $this->faceRecognitionService->makeTempPath('temp_attendance_');
                file_put_contents($tempPath, $imageBinary);

                $recognitionResult = $this->faceRecognitionService->recognizeFace($tempPath);

                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }

                if ($recognitionResult['matched']) {
                    if ((int) $recognitionResult['id_karyawan'] === $idKaryawan) {
                        $faceVerified   = true;
                        $faceConfidence = $recognitionResult['confidence'];
                    } else {
                        // Matched to a different employee — reject
                        return response()->json([
                            'success' => false,
                            'message' => 'Wajah yang tertangkap bukan milik karyawan yang dipilih.',
                            'code'    => 'FACE_MISMATCH',
                        ], 422);
                    }
                }
                // If not matched: proceed but face_verified stays false
            }

            $result = $this->attendanceService->adminRecord($idKaryawan, [
                'status'          => $status,
                'jam_masuk'       => $request->jam_masuk,
                'recorded_by'     => Auth::user()->id_user,
                'face_verified'   => $faceVerified,
                'face_confidence' => $faceConfidence,
                'photo_hash'      => $photoHash,
            ]);

            $karyawan = \App\Models\Karyawan::find($idKaryawan);

            return response()->json([
                'success'        => $result['success'],
                'message'        => $result['message'],
                'face_verified'  => $faceVerified,
                'face_confidence' => $faceConfidence,
                'employee_name'  => $karyawan?->nama,
                'status'         => $status,
            ], $result['success'] ? 200 : 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("adminRecord error: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server.'], 500);
        }
    }
}
