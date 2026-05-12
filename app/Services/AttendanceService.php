<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\JadwalKerja;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * Process clock in for an employee
     * Validates schedule and records attendance
     *
     * @param int $idKaryawan
     * @return array ['success' => bool, 'message' => string, 'attendance' => Absensi|null]
     */
    public function clockIn(int $idKaryawan): array
    {
        try {
            $karyawan = Karyawan::find($idKaryawan);
            if (!$karyawan) {
                return ['success' => false, 'message' => 'Employee not found'];
            }

            $today = Carbon::today();

            // Check if already clocked in today
            $existingAttendance = Absensi::where('id_karyawan', $idKaryawan)
                ->where('tanggal', $today)
                ->first();

            if ($existingAttendance && $existingAttendance->jam_masuk) {
                return [
                    'success' => false,
                    'message' => 'Already clocked in today',
                    'attendance' => $existingAttendance,
                ];
            }

            // Validate schedule - check if employee should be working today
            $schedule = $this->getTodaySchedule($idKaryawan);

            if ($schedule && $schedule->isLibur()) {
                return [
                    'success' => false,
                    'message' => 'Today is a day off',
                    'attendance' => null,
                ];
            }

            $now = Carbon::now();
            $jamMasuk = $now->format('H:i:s');
            $referenceShift = $this->resolveReferenceShift($karyawan, $schedule);

            // Check if late
            $isLate = false;
            $lateMinutes = 0;
            $expectedStartTime = '08:00:00'; // Default start time

            if ($schedule || $referenceShift) {
                $shiftTime = $this->extractShiftStartTime($schedule, $referenceShift);
                if ($shiftTime) {
                    $expectedStartTime = $shiftTime;
                    $expectedStart = Carbon::today()->setTimeFromTimeString($shiftTime);
                    if ($now->gt($expectedStart)) {
                        $isLate = true;
                        $lateMinutes = $expectedStart->diffInMinutes($now);
                    }
                }
            } else {
                // No schedule found, allow check-in but mark as potentially flexible
                $fallbackStart = Carbon::today()->setTimeFromTimeString('09:00:00');
                if ($now->gt($fallbackStart)) {
                    $isLate = true;
                    $lateMinutes = $fallbackStart->diffInMinutes($now);
                }
            }

            // Create or update attendance record
            $attendance = Absensi::updateOrCreate(
                [
                    'id_karyawan' => $idKaryawan,
                    'tanggal' => $today,
                ],
                [
                    'jam_masuk' => $jamMasuk,
                    'status' => $isLate ? 'terlambat' : 'tepat_waktu',
                    'menit_terlambat' => $isLate ? $lateMinutes : 0,
                ]
            );

            Log::info("Clock in successful for employee {$idKaryawan}", [
                'time' => $jamMasuk,
                'status' => $attendance->status,
            ]);

            return [
                'success' => true,
                'message' => $isLate ? 'Clocked in (Late)' : 'Clocked in (On time)',
                'attendance' => $attendance,
            ];
        } catch (\Exception $e) {
            Log::error("Clock in error: {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Error during clock in: ' . $e->getMessage(),
                'attendance' => null,
            ];
        }
    }

    /**
     * Process clock out for an employee
     *
     * @param int $idKaryawan
     * @return array ['success' => bool, 'message' => string, 'attendance' => Absensi|null]
     */
    public function clockOut(int $idKaryawan): array
    {
        try {
            $karyawan = Karyawan::find($idKaryawan);
            if (!$karyawan) {
                return ['success' => false, 'message' => 'Employee not found'];
            }

            $today = Carbon::today();

            $attendance = Absensi::where('id_karyawan', $idKaryawan)
                ->where('tanggal', $today)
                ->first();

            if (!$attendance) {
                return [
                    'success' => false,
                    'message' => 'No clock in record found for today',
                    'attendance' => null,
                ];
            }

            if ($attendance->jam_pulang) {
                return [
                    'success' => false,
                    'message' => 'Already clocked out today',
                    'attendance' => $attendance,
                ];
            }

            if (!$attendance->jam_masuk) {
                return [
                    'success' => false,
                    'message' => 'Jam masuk belum tersedia, belum bisa absen pulang.',
                    'attendance' => $attendance,
                ];
            }

            $clockInAt = Carbon::today()->setTimeFromTimeString($attendance->jam_masuk);
            $minimumClockOutAt = (clone $clockInAt)->addHours(5);
            if (Carbon::now()->lt($minimumClockOutAt)) {
                $remainingMinutes = Carbon::now()->diffInMinutes($minimumClockOutAt);
                return [
                    'success' => false,
                    'message' => "Absen pulang tersedia minimal 5 jam setelah jam masuk. Sisa {$remainingMinutes} menit lagi.",
                    'attendance' => $attendance,
                ];
            }

            $schedule = $this->getTodaySchedule($idKaryawan);
            $referenceShift = $this->resolveReferenceShift($karyawan, $schedule);
            if ($schedule && !$schedule->isLibur()) {
                $shiftEndTime = $this->extractShiftEndTime($schedule, $referenceShift);
                if ($shiftEndTime) {
                    $allowedClockOutFrom = Carbon::today()
                        ->setTimeFromTimeString($shiftEndTime)
                        ->subMinutes(30);

                    if (Carbon::now()->lt($allowedClockOutFrom)) {
                        return [
                            'success' => false,
                            'message' => 'Belum mendekati jam pulang sesuai shift',
                            'attendance' => $attendance,
                        ];
                    }
                }
            } elseif ($referenceShift && $referenceShift->jam_pulang) {
                $allowedClockOutFrom = Carbon::today()
                    ->setTimeFromTimeString($referenceShift->jam_pulang)
                    ->subMinutes(30);

                if (Carbon::now()->lt($allowedClockOutFrom)) {
                    return [
                        'success' => false,
                        'message' => 'Belum mendekati jam pulang sesuai shift',
                        'attendance' => $attendance,
                    ];
                }
            }

            $jamPulang = Carbon::now()->format('H:i:s');
            $attendance->jam_pulang = $jamPulang;
            $attendance->save();

            Log::info("Clock out successful for employee {$idKaryawan}", [
                'time' => $jamPulang,
            ]);

            return [
                'success' => true,
                'message' => 'Clocked out',
                'attendance' => $attendance,
            ];
        } catch (\Exception $e) {
            Log::error("Clock out error: {$e->getMessage()}");
            return [
                'success' => false,
                'message' => 'Error during clock out: ' . $e->getMessage(),
                'attendance' => null,
            ];
        }
    }

    /**
     * Auto-detect and process attendance (system-driven, not user-driven)
     *
     * Determines whether to clock in or clock out based on current state:
     * - No record today → CLOCK IN
     * - Record exists but no jam_pulang → CLOCK OUT
     * - Both exist → ALREADY COMPLETED (reject)
     *
     * @param int $idKaryawan
     * @param string|null $preferredAction 'clock_in'|'clock_out' or null for auto
     * @return array ['success' => bool, 'action' => 'clock_in'|'clock_out', 'message' => string, 'attendance' => Absensi|null, 'status' => string|null]
     */
    public function processAutoAttendance(int $idKaryawan, ?string $preferredAction = null): array
    {
        try {
            $karyawan = Karyawan::find($idKaryawan);
            if (!$karyawan) {
                return [
                    'success' => false,
                    'action' => null,
                    'message' => 'Employee not found',
                    'attendance' => null,
                    'status' => null,
                ];
            }

            $today = Carbon::today();
            $todayAttendance = Absensi::where('id_karyawan', $idKaryawan)
                ->where('tanggal', $today)
                ->first();

            // Determine action based on state
            $action = null;

            if (!$todayAttendance) {
                // No record yet → CLOCK IN
                $action = 'clock_in';
            } elseif ($todayAttendance->jam_masuk && !$todayAttendance->jam_pulang) {
                // Clocked in but not out → CLOCK OUT
                $action = 'clock_out';
            } else {
                // Both exist → Already completed
                return [
                    'success' => false,
                    'action' => null,
                    'message' => 'Attendance already completed for today',
                    'attendance' => $todayAttendance,
                    'status' => null,
                ];
            }

            if ($preferredAction && $preferredAction !== $action) {
                $expectedLabel = $action === 'clock_in' ? 'masuk' : 'pulang';
                $selectedLabel = $preferredAction === 'clock_in' ? 'masuk' : 'pulang';

                return [
                    'success' => false,
                    'action' => $action,
                    'message' => "Aksi {$selectedLabel} belum sesuai. Silakan lakukan absen {$expectedLabel}.",
                    'attendance' => $todayAttendance,
                    'status' => null,
                ];
            }

            // Process the determined action
            if ($action === 'clock_in') {
                $result = $this->clockIn($idKaryawan);
            } else {
                $result = $this->clockOut($idKaryawan);
            }

            // Add action field to result
            $result['action'] = $action;

            // Add status field (tepat_waktu, terlambat, or pulang for clock out)
            if ($result['attendance']) {
                $result['status'] = $result['attendance']->status ?? ($action === 'clock_out' ? 'pulang' : null);
            } else {
                $result['status'] = null;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Auto attendance process error: {$e->getMessage()}");
            return [
                'success' => false,
                'action' => null,
                'message' => 'Error processing attendance: ' . $e->getMessage(),
                'attendance' => null,
                'status' => null,
            ];
        }
    }

    /**
     * Get today's work schedule for an employee
     *
     * @param int $idKaryawan
     * @return JadwalKerja|null
     */
    private function getTodaySchedule(int $idKaryawan): ?JadwalKerja
    {
        return JadwalKerja::where('id_karyawan', $idKaryawan)
            ->where('tanggal', Carbon::today())
            ->with('shift')
            ->first();
    }

    /**
     * Extract start time from shift description
     * e.g., "Pagi (08:00-17:00)" → "08:00:00"
     *
     * @param JadwalKerja $schedule
     * @return string|null
     */
    private function extractShiftStartTime(?JadwalKerja $schedule, ?Shift $referenceShift = null): ?string
    {
        if ($schedule && $schedule->shift && $schedule->shift->jam_masuk) {
            return $schedule->shift->jam_masuk;
        }

        if ($referenceShift && $referenceShift->jam_masuk) {
            return $referenceShift->jam_masuk;
        }

        $jamKerja = $schedule?->jam_kerja;
        if ($jamKerja && preg_match('/\((\d{2}):(\d{2})-/', $jamKerja, $matches)) {
            return "{$matches[1]}:{$matches[2]}:00";
        }
        return null;
    }

    private function extractShiftEndTime(?JadwalKerja $schedule, ?Shift $referenceShift = null): ?string
    {
        if ($schedule && $schedule->shift && $schedule->shift->jam_pulang) {
            return $schedule->shift->jam_pulang;
        }

        if ($referenceShift && $referenceShift->jam_pulang) {
            return $referenceShift->jam_pulang;
        }

        $jamKerja = $schedule?->jam_kerja;
        if ($jamKerja && preg_match('/-(\d{2}):(\d{2})\)/', $jamKerja, $matches)) {
            return "{$matches[1]}:{$matches[2]}:00";
        }

        return null;
    }

    private function resolveReferenceShift(Karyawan $karyawan, ?JadwalKerja $schedule): ?Shift
    {
        if ($schedule && $schedule->shift) {
            return $schedule->shift;
        }

        if ($karyawan->relationLoaded('shift')) {
            return $karyawan->shift;
        }

        return $karyawan->shift()->first();
    }

    /**
     * Get attendance history for an employee
     *
     * @param int $idKaryawan
     * @param int $days Number of recent days to retrieve
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAttendanceHistory(int $idKaryawan, int $days = 30)
    {
        return Absensi::where('id_karyawan', $idKaryawan)
            ->where('tanggal', '>=', Carbon::today()->subDays($days))
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    /**
     * Get today's attendance summary
     *
     * @return array
     */
    public function getTodaysSummary(): array
    {
        $today = Carbon::today();

        $totalPresent = Absensi::where('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->count();

        $totalLate = Absensi::where('tanggal', $today)
            ->where('status', 'terlambat')
            ->count();

        $totalAbsent = Karyawan::count() - $totalPresent;

        return [
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_absent' => max(0, $totalAbsent),
            'date' => $today->format('Y-m-d'),
        ];
    }

    /**
     * Record attendance by admin (admin-controlled flow).
     *
     * @param int   $idKaryawan
     * @param array $data  Keys: status, jam_masuk, recorded_by, face_verified,
     *                     face_confidence, photo_hash, gps_lat, gps_lng,
     *                     device_info, ip_address
     * @return array ['success' => bool, 'message' => string, 'attendance' => Absensi|null]
     */
    public function adminRecord(int $idKaryawan, array $data): array
    {
        try {
            $karyawan = Karyawan::find($idKaryawan);
            if (!$karyawan) {
                return ['success' => false, 'message' => 'Karyawan tidak ditemukan.', 'attendance' => null];
            }

            $today = Carbon::today();

            // Prevent duplicate / editing locked records
            $existing = Absensi::where('id_karyawan', $idKaryawan)
                ->whereDate('tanggal', $today)
                ->first();

            if ($existing && $existing->is_locked) {
                return [
                    'success'    => false,
                    'message'    => 'Absensi hari ini sudah dikunci dan tidak dapat diubah.',
                    'attendance' => $existing,
                ];
            }

            $status   = $data['status'];
            $jamMasuk = null;

            // Only set jam_masuk for statuses where employee is present/working
            if (in_array($status, ['hadir', 'terlambat', 'remote'])) {
                $jamMasuk = $data['jam_masuk'] ?? Carbon::now()->format('H:i:s');
                // Normalise H:i → H:i:s
                if (strlen($jamMasuk) === 5) {
                    $jamMasuk .= ':00';
                }
            }

            $attendance = Absensi::updateOrCreate(
                ['id_karyawan' => $idKaryawan, 'tanggal' => $today],
                [
                    'jam_masuk'       => $jamMasuk,
                    'jam_pulang'      => null,
                    'status'          => $status,
                    'recorded_by'     => $data['recorded_by'],
                    'face_verified'   => $data['face_verified'] ?? false,
                    'face_confidence' => $data['face_confidence'] ?? null,
                    'photo_hash'      => $data['photo_hash'] ?? null,
                    'gps_lat'         => $data['gps_lat'] ?? null,
                    'gps_lng'         => $data['gps_lng'] ?? null,
                    'device_info'     => $data['device_info'] ?? null,
                    'ip_address'      => $data['ip_address'] ?? null,
                    'is_locked'       => true,
                ]
            );

            Log::info("Admin attendance recorded", [
                'id_karyawan' => $idKaryawan,
                'status'      => $status,
                'recorded_by' => $data['recorded_by'],
                'face_verified' => $data['face_verified'] ?? false,
            ]);

            return [
                'success'    => true,
                'message'    => 'Absensi berhasil dicatat.',
                'attendance' => $attendance,
            ];
        } catch (\Exception $e) {
            Log::error("Admin record attendance error: {$e->getMessage()}");
            return [
                'success'    => false,
                'message'    => 'Terjadi kesalahan saat mencatat absensi.',
                'attendance' => null,
            ];
        }
    }
}
