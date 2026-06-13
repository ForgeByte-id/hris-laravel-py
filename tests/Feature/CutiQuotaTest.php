<?php

namespace Tests\Feature;

use App\Models\Cuti;
use App\Models\Devisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\LeaveType;
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
        $this->seedLeaveTypes();
        $employee = $this->createEmployeeUser('karyawan', 12, 12);
        $this->actingAs($employee['user']);

        $response = $this->post(route('cuti.store'), [
            'jenis_cuti' => 'Cuti Tahunan',
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
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('karyawan', 12, 12);
        $this->actingAs($admin);

        $response = $this->post(route('cuti.store'), [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Cuti Tahunan',
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
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('karyawan', 12, 1);
        $this->actingAs($admin);

        $cuti = Cuti::create([
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Cuti Tahunan',
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

    public function test_holiday_leave_uses_its_own_quota_balance(): void
    {
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('karyawan', 12, 12);
        $this->actingAs($admin);

        $response = $this->post(route('cuti.store'), [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Cuti Hari Raya',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDays(4)->format('Y-m-d'),
            'keterangan' => 'Cuti hari raya',
        ]);

        $response->assertRedirect(route('cuti.index'));
        $this->assertDatabaseHas('karyawan_leave_quotas', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'remaining_quota' => 0,
        ]);

        $employee['karyawan']->refresh();
        $this->assertSame(12, $employee['karyawan']->remaining_leave_quota);
    }

    public function test_contract_employee_cannot_use_annual_leave_by_default(): void
    {
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('karyawan', 0, 0, 'Kontrak');
        $this->actingAs($admin);

        $response = $this->post(route('cuti.store'), [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Cuti Tahunan',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDay()->format('Y-m-d'),
            'keterangan' => 'Cuti tahunan kontrak',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('cuti', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Cuti Tahunan',
        ]);
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

    private function createEmployeeUser(string $role, int $yearlyQuota, int $remainingQuota, string $statusKaryawan = 'Tetap'): array
    {
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff']);
        $divisi = Devisi::firstOrCreate(['nama_devisi' => 'Divisi Test']);

        $user = User::create([
            'username' => 'employee_' . uniqid(),
            'password' => bcrypt('password'),
            'role' => $role,
        ]);

        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Employee Test',
            'id_jabatan' => $jabatan->id,
            'id_devisi' => $divisi->id,
            'tanggal_masuk' => Carbon::today()->format('Y-m-d'),
            'status_aktif' => 'Aktif',
            'status_karyawan' => $statusKaryawan,
            'yearly_leave_quota' => $yearlyQuota,
            'remaining_leave_quota' => $remainingQuota,
        ]);

        return ['user' => $user, 'karyawan' => $karyawan];
    }

    private function seedLeaveTypes(): void
    {
        foreach ([
            ['nama_cuti' => 'Cuti Tahunan', 'default_quota' => 12, 'applies_to_status' => 'Tetap'],
            ['nama_cuti' => 'Cuti Hari Raya', 'default_quota' => 4, 'applies_to_status' => null],
            ['nama_cuti' => 'Cuti Sakit', 'default_quota' => 6, 'applies_to_status' => null],
        ] as $leaveType) {
            LeaveType::updateOrCreate(
                ['nama_cuti' => $leaveType['nama_cuti']],
                $leaveType + ['is_active' => true]
            );
        }
    }
}
