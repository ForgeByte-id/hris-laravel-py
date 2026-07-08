<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_out_is_opened_near_shift_end_even_if_less_than_five_hours_after_clock_in(): void
    {
        $service = app(AttendanceService::class);
        $karyawan = $this->createEmployeeWithShift('Pa', '11:00:00', '20:00:00');

        Carbon::setTestNow(Carbon::today()->setTime(19, 50));
        $clockInResult = $service->clockIn($karyawan->id_karyawan);
        $this->assertTrue($clockInResult['success']);
        $this->assertSame('terlambat', $clockInResult['attendance']->status);

        Carbon::setTestNow(Carbon::today()->setTime(19, 51));
        $clockOutResult = $service->clockOut($karyawan->id_karyawan);

        $this->assertTrue($clockOutResult['success']);
        $this->assertNotNull($clockOutResult['attendance']->jam_pulang);
    }

    public function test_clock_out_remains_blocked_before_shift_end_window(): void
    {
        $service = app(AttendanceService::class);
        $karyawan = $this->createEmployeeWithShift('Pa', '11:00:00', '20:00:00');

        Absensi::create([
            'id_karyawan' => $karyawan->id_karyawan,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_masuk' => '18:00:00',
            'status' => 'terlambat',
            'menit_terlambat' => 420,
        ]);

        Carbon::setTestNow(Carbon::today()->setTime(18, 10));
        $clockOutResult = $service->clockOut($karyawan->id_karyawan);

        $this->assertFalse($clockOutResult['success']);
        $this->assertSame('Belum mendekati jam pulang sesuai shift', $clockOutResult['message']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createEmployeeWithShift(string $kodeShift, string $jamMasuk, string $jamPulang): Karyawan
    {
        Shift::create([
            'kode_shift' => $kodeShift,
            'nama_shift' => 'Pagi',
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
        ]);

        $user = User::create([
            'username' => 'employee_' . uniqid(),
            'password' => bcrypt('password'),
            'role' => 'karyawan',
        ]);

        return Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Employee Shift',
            'tanggal_masuk' => Carbon::today()->toDateString(),
        ]);
    }
}
