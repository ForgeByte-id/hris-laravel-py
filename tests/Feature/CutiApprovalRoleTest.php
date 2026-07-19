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

    public function test_division_head_only_sees_pending_leave_from_same_division_after_sdm_approval(): void
    {
        $this->seedLeaveTypes();
        $divisionA = Divisi::firstOrCreate(['nama_divisi' => 'Divisi A']);
        $divisionB = Divisi::firstOrCreate(['nama_divisi' => 'Divisi B']);
        
        $head = $this->createEmployeeWithRole('atasan_divisi', $divisionA, 'Atasan A', 'Manager Divisi');
        $sdmA = $this->createEmployeeWithRole('admin', $divisionA, 'SDM A', 'SDM');
        $employeeA = $this->createEmployeeWithRole('karyawan', $divisionA, 'Employee A', 'Staff');
        $employeeB = $this->createEmployeeWithRole('karyawan', $divisionB, 'Employee B', 'Staff');

        $cutiA = $this->createPendingLeave($employeeA['karyawan']);
        $cutiB = $this->createPendingLeave($employeeB['karyawan']);

        // SDM approves Cuti A first so it moves to level 2 (Division Head)
        $this->actingAs($sdmA['user'])->patch(route('cuti.update-status', $cutiA->id_cuti), ['status' => 'approved']);

        $response = $this->actingAs($head['user'])->get(route('cuti.approval'));

        $response->assertOk();
        $response->assertSee($employeeA['karyawan']->nama);
        $response->assertDontSee($employeeB['karyawan']->nama);
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
        
        $sdm = $this->createEmployeeWithRole('admin', $division, 'SDM Admin Test', 'SDM');
        $md = $this->createEmployeeWithRole('atasan_divisi', $division, 'MD Admin Test', 'Manager Divisi');
        $mu = $this->createEmployeeWithRole('atasan_divisi', $division, 'MU Admin Test', 'Manager Umum');
        
        $employee = $this->createEmployeeWithRole('karyawan', $division, 'Employee Admin Test');
        $cuti = $this->createPendingLeave($employee['karyawan']);

        // Level 1: SDM approval
        $response1 = $this->actingAs($sdm['user'])->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ]);
        $response1->assertRedirect();

        // Level 2: Manager Divisi approval
        $response2 = $this->actingAs($md['user'])->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ]);
        $response2->assertRedirect();

        // Level 3: Manager Umum approval (final approval)
        $response3 = $this->actingAs($mu['user'])->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ]);
        $response3->assertRedirect();

        $this->assertDatabaseHas('cuti', [
            'id_cuti' => $cuti->id_cuti,
            'status_persetujuan' => 'approved',
        ]);
    }

    public function test_manager_divisi_leave_goes_directly_to_manager_umum(): void
    {
        $this->seedLeaveTypes();
        $division = Divisi::firstOrCreate(['nama_divisi' => 'Divisi Manager']);

        $managerDivisi = $this->createEmployeeWithRole('atasan_divisi', $division, 'Manager Divisi A', 'Manager Divisi');
        $managerUmum = $this->createEmployeeWithRole('atasan_divisi', $division, 'Manager Umum A', 'Manager Umum');
        $cuti = $this->createPendingLeave($managerDivisi['karyawan']);

        $response = $this->actingAs($managerUmum['user'])->get(route('cuti.approval'));
        $response->assertOk();
        $response->assertSee($managerDivisi['karyawan']->nama);

        $this->actingAs($managerUmum['user'])->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ])->assertRedirect();

        $this->assertDatabaseHas('cuti', [
            'id_cuti' => $cuti->id_cuti,
            'status_persetujuan' => 'approved',
        ]);
    }

    public function test_manager_umum_leave_goes_directly_to_management(): void
    {
        $this->seedLeaveTypes();
        $division = Divisi::firstOrCreate(['nama_divisi' => 'Divisi Management']);

        $managerUmum = $this->createEmployeeWithRole('atasan_divisi', $division, 'Manager Umum B', 'Manager Umum');
        $management = $this->createEmployeeWithRole('Management', $division, 'BOD A', 'Staff');
        $cuti = $this->createPendingLeave($managerUmum['karyawan']);

        $response = $this->actingAs($management['user'])->get(route('cuti.approval'));
        $response->assertOk();
        $response->assertSee($managerUmum['karyawan']->nama);

        $this->actingAs($management['user'])->patch(route('cuti.update-status', $cuti->id_cuti), [
            'status' => 'approved',
        ])->assertRedirect();

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

    private function createEmployeeWithRole(string $role, Divisi $division, string $name, string $namaJabatan = 'Staff'): array
    {
        $user = $this->createUserWithRole($role, str($name)->slug('_')->value());
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => $namaJabatan]);

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
