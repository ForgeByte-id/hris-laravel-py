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

class CutiApprovalRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_division_head_only_sees_pending_leave_from_same_division(): void
    {
        $this->seedLeaveTypes();
        $divisionA = Devisi::firstOrCreate(['nama_devisi' => 'Divisi A']);
        $divisionB = Devisi::firstOrCreate(['nama_devisi' => 'Divisi B']);
        $head = $this->createEmployeeWithRole('atasan_divisi', $divisionA, 'Atasan A');
        $employeeA = $this->createEmployeeWithRole('karyawan', $divisionA, 'Employee A');
        $employeeB = $this->createEmployeeWithRole('karyawan', $divisionB, 'Employee B');

        $cutiA = $this->createPendingLeave($employeeA['karyawan']);
        $cutiB = $this->createPendingLeave($employeeB['karyawan']);

        $response = $this->actingAs($head['user'])->get(route('cuti.approval'));

        $response->assertOk();
        $response->assertSee($employeeA['karyawan']->nama);
        $response->assertDontSee($employeeB['karyawan']->nama);
        $this->assertDatabaseHas('cuti', ['id_cuti' => $cutiA->id_cuti]);
        $this->assertDatabaseHas('cuti', ['id_cuti' => $cutiB->id_cuti]);
    }

    public function test_hr_cannot_update_leave_status(): void
    {
        $this->seedLeaveTypes();
        $division = Devisi::firstOrCreate(['nama_devisi' => 'Divisi HR Test']);
        $hr = $this->createUserWithRole('hr');
        $employee = $this->createEmployeeWithRole('karyawan', $division, 'Employee HR Test');
        $cuti = $this->createPendingLeave($employee['karyawan']);

        $response = $this->actingAs($hr)->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('cuti', [
            'id_cuti' => $cuti->id_cuti,
            'status_persetujuan' => 'pending',
        ]);
    }

    public function test_admin_can_approve_any_pending_leave(): void
    {
        $this->seedLeaveTypes();
        $division = Devisi::firstOrCreate(['nama_devisi' => 'Divisi Admin Test']);
        $admin = $this->createUserWithRole('admin');
        $employee = $this->createEmployeeWithRole('karyawan', $division, 'Employee Admin Test');
        $cuti = $this->createPendingLeave($employee['karyawan']);

        $response = $this->actingAs($admin)->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cuti', [
            'id_cuti' => $cuti->id_cuti,
            'status_persetujuan' => 'approved',
        ]);
    }

    private function createPendingLeave(Karyawan $karyawan): Cuti
    {
        return Cuti::create([
            'id_karyawan' => $karyawan->id_karyawan,
            'jenis_cuti' => 'Cuti Tahunan',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'keterangan' => 'Pengajuan test',
            'status_persetujuan' => 'pending',
        ]);
    }

    private function createEmployeeWithRole(string $role, Devisi $division, string $name): array
    {
        $user = $this->createUserWithRole($role, str($name)->slug('_')->value());
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff']);

        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => $name,
            'id_jabatan' => $jabatan->id,
            'id_devisi' => $division->id,
            'tanggal_masuk' => Carbon::today()->format('Y-m-d'),
            'status_aktif' => 'Aktif',
            'status_karyawan' => 'Tetap',
            'yearly_leave_quota' => 12,
            'remaining_leave_quota' => 12,
        ]);

        return ['user' => $user, 'karyawan' => $karyawan];
    }

    private function createUserWithRole(string $role, ?string $username = null): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::create([
            'username' => $username ?: $role . '_' . uniqid(),
            'password' => 'password',
            'role' => $role,
        ]);

        $user->assignRole($role);

        return $user;
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
