<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardDailyRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_daily_recap_without_remote_card(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::create([
            'username' => 'dashboard_admin',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $employeeUser = User::create([
            'username' => 'dashboard_employee',
            'password' => 'password',
            'role' => 'karyawan',
        ]);
        $employee = Karyawan::create([
            'id_user' => $employeeUser->id_user,
            'nama' => 'Dashboard Employee',
            'tanggal_masuk' => Carbon::today()->toDateString(),
            'status_aktif' => 'Aktif',
            'status_karyawan' => 'Tetap',
            'yearly_leave_quota' => 12,
            'remaining_leave_quota' => 12,
        ]);
        Absensi::create([
            'id_karyawan' => $employee->id_karyawan,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00',
            'status' => 'remote',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Total Karyawan');
        $response->assertSee('Sudah Absen Masuk');
        $response->assertDontSee('>Remote<', false);
    }
}
