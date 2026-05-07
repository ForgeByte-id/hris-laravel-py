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
     * Show the attendance check-in page (camera interface)
     */
    public function index(): View
    {
        // Check if face recognition service is available
        $serviceHealthy = $this->faceRecognitionService->healthCheck();

        return view('absensi.attendance_index', [
            'serviceHealthy' => $serviceHealthy,
        ]);
    }

    /**
     * Show attendance history
     */
    public function history(Request $request): View
    {
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
     *
     * System-driven: Backend determines whether it's clock-in or clock-out
     * based on current attendance state, not user input
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|string',
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

            // Save temporary image
            $tempPath = storage_path('app/temp_attendance_' . uniqid() . '.jpg');
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

            // Auto-detect and process attendance (system decides, not user)
            $result = $this->attendanceService->processAutoAttendance($idKaryawan);

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
     *
     * Authorization: Only admin can view global summary
     * - Admin: returns total summary for entire organization
     * - Others: returns 403 Forbidden
     */
    public function todaysSummary(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Only admin can view global summary
            if (!$this->authorizationService->canViewAttendanceSummary($user)) {
                return response()->json([
                    'error' => 'Unauthorized: Only admin can view attendance summary',
                    'code' => 'UNAUTHORIZED_SUMMARY_ACCESS'
                ], 403);
            }

            $summary = $this->attendanceService->getTodaysSummary();
            return response()->json($summary);
        } catch (\Exception $e) {
            Log::error("Get todays summary error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get employee's current attendance status (for dashboard)
     *
     * Authorization:
     * - Admin/HR: can view any employee
     * - Regular employee: can only view their own
     */
    public function getCurrentStatus($idKaryawan): JsonResponse
    {
        try {
            $user = Auth::user();

            // Authorization check: Can user view this employee's data?
            if (!$this->authorizationService->canViewAttendanceRecord($user, $idKaryawan)) {
                return response()->json([
                    'error' => 'Unauthorized: You can only view your own attendance record',
                    'code' => 'UNAUTHORIZED_RECORD_ACCESS'
                ], 403);
            }

            $karyawan = Karyawan::find($idKaryawan);
            if (!$karyawan) {
                return response()->json(['error' => 'Employee not found'], 404);
            }

            $attendance = $this->attendanceService->getAttendanceHistory($idKaryawan, 1)->first();

            return response()->json([
                'employee' => $karyawan->nama,
                'clock_in' => $attendance?->jam_masuk,
                'clock_out' => $attendance?->jam_pulang,
                'status' => $attendance?->status,
                'date' => $attendance?->tanggal,
            ]);
        } catch (\Exception $e) {
            Log::error("Get current status error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getHistory($idKaryawan): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$this->authorizationService->canViewAttendanceRecord($user, $idKaryawan)) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'code' => 'UNAUTHORIZED_RECORD_ACCESS'
                ], 403);
            }

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
}
