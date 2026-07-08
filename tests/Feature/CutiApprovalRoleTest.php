<?php

namespace Tests\Feature;

use App\Models\Cuti;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\TipeCuti;
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
        $divisionA = Divisi::firstOrCreate(['nama_divisi' => 'Divisi A']);
        $divisionB = Divisi::firstOrCreate(['nama_divisi' => 'Divisi B']);
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
        $division = Divisi::firstOrCreate(['nama_divisi' => 'Divisi HR Test']);
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
        $division = Divisi::firstOrCreate(['nama_divisi' => 'Divisi Admin Test']);
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

    private function createEmployeeWithRole(string $role, Divisi $division, string $name): array
    {
        $user = $this->createUserWithRole($role, str($name)->slug('_')->value());
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff']);

        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => $name,
            'id_jabatan' => $jabatan->id,
            'id_divisi' => $division->id,
            'tanggal_masuk' => Carbon::today()->format('Y-m-d'),
            'status_aktif' => 'Aktif',
            'status_karyawan' => 'Tetap',
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
            ['nama_cuti' => 'Cuti Tahunan', 'kuota_cuti' => 12, 'berlaku_untuk_status' => 'Tetap'],
            ['nama_cuti' => 'Cuti Hari Raya', 'kuota_cuti' => 4, 'berlaku_untuk_status' => null],
            ['nama_cuti' => 'Cuti Sakit', 'kuota_cuti' => 6, 'berlaku_untuk_status' => null],
        ] as $leaveType) {
            TipeCuti::updateOrCreate(
                ['nama_cuti' => $leaveType['nama_cuti']],
                $leaveType + ['is_active' => true]
            );
        }
    }
}
