<?php

namespace Tests\Feature;

use App\Models\Cuti;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CutiQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_leave_submission_stays_pending_and_does_not_reduce_quota(): void
    {
        $employee = $this->createEmployeeUser('karyawan', 12, 12);
        $this->actingAs($employee['user']);

        $response = $this->post(route('cuti.store'), [
            'jenis_cuti' => 'Tahunan',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'keterangan' => 'Tes pengajuan cuti',
        ]);

        $response->assertRedirect(route('cuti.index'));
        $this->assertDatabaseHas('cuti', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'status_persetujuan' => 'pending',
        ]);

        $employee['karyawan']->refresh();
        $this->assertSame(12, $employee['karyawan']->remaining_leave_quota);
    }

    public function test_admin_can_create_approved_leave_and_reduce_employee_quota(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('karyawan', 12, 12);
        $this->actingAs($admin);

        $response = $this->post(route('cuti.store'), [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Tahunan',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'keterangan' => 'Admin input cuti',
        ]);

        $response->assertRedirect(route('cuti.index'));
        $this->assertDatabaseHas('cuti', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'status_persetujuan' => 'approved',
        ]);

        $employee['karyawan']->refresh();
        $this->assertSame(10, $employee['karyawan']->remaining_leave_quota);
    }

    public function test_approval_is_rejected_when_remaining_quota_is_not_enough(): void
    {
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('karyawan', 12, 1);
        $this->actingAs($admin);

        $cuti = Cuti::create([
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Tahunan',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'keterangan' => 'Cuti melebihi kuota',
            'status_persetujuan' => 'pending',
            'id_atasan' => null,
        ]);

        $response = $this->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('cuti', [
            'id_cuti' => $cuti->id_cuti,
            'status_persetujuan' => 'pending',
        ]);

        $employee['karyawan']->refresh();
        $this->assertSame(1, $employee['karyawan']->remaining_leave_quota);
    }

    private function createAdminUser(): User
    {
        Role::findOrCreate('admin', 'web');

        $user = User::create([
            'username' => 'admin_user',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $user->assignRole('admin');

        return $user;
    }

    private function createEmployeeUser(string $role, int $yearlyQuota, int $remainingQuota): array
    {
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff']);

        $user = User::create([
            'username' => 'employee_' . uniqid(),
            'password' => bcrypt('password'),
            'role' => $role,
        ]);

        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Employee Test',
            'id_jabatan' => $jabatan->id,
            'tanggal_masuk' => Carbon::today()->format('Y-m-d'),
            'yearly_leave_quota' => $yearlyQuota,
            'remaining_leave_quota' => $remainingQuota,
        ]);

        return ['user' => $user, 'karyawan' => $karyawan];
    }
}
