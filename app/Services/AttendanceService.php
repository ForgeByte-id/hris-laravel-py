<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\JadwalKerja;
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

            // Check if late
            $isLate = false;
            $expectedStartTime = '08:00:00'; // Default start time

            if ($schedule) {
                $shiftTime = $this->extractShiftStartTime($schedule->jam_kerja);
                if ($shiftTime) {
                    $expectedStartTime = $shiftTime;
                    $isLate = $now->format('H:i:s') > $shiftTime;
                }
            } else {
                // No schedule found, allow check-in but mark as potentially flexible
                $isLate = $now->format('H:i:s') > '09:00:00';
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
     * @return array ['success' => bool, 'action' => 'clock_in'|'clock_out', 'message' => string, 'attendance' => Absensi|null, 'status' => string|null]
     */
    public function processAutoAttendance(int $idKaryawan): array
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
            ->first();
    }

    /**
     * Extract start time from shift description
     * e.g., "Pagi (08:00-17:00)" → "08:00:00"
     *
     * @param string $jamKerja
     * @return string|null
     */
    private function extractShiftStartTime(string $jamKerja): ?string
    {
        if (preg_match('/\((\d{2}):(\d{2})-/', $jamKerja, $matches)) {
            return "{$matches[1]}:{$matches[2]}:00";
        }
        return null;
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
}
