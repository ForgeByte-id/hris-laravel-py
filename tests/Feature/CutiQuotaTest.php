<?php

namespace Tests\Feature;

use App\Models\Cuti;
use App\Models\Divisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KuotaCutiKaryawan;
use App\Models\TipeCuti;
use App\Models\User;
use App\Services\LeaveQuotaService;
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
        $employee = $this->createEmployeeUser();
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

        $this->assertDatabaseHas('kuota_cuti_karyawan', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'remaining_quota' => 12,
        ]);
    }

    public function test_admin_can_create_approved_leave_and_reduce_employee_quota(): void
    {
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser();
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

        $this->assertDatabaseHas('kuota_cuti_karyawan', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'remaining_quota' => 10,
        ]);
    }

    public function test_approval_is_rejected_when_remaining_quota_is_not_enough(): void
    {
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();

        $employee = $this->createEmployeeUser();
        app(LeaveQuotaService::class)->ensureBalancesFor($employee['karyawan']);
        KuotaCutiKaryawan::where('id_karyawan', $employee['karyawan']->id_karyawan)
            ->update(['remaining_quota' => 1]);
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

        $this->assertDatabaseHas('kuota_cuti_karyawan', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'remaining_quota' => 1,
        ]);
    }

    public function test_holiday_leave_uses_its_own_quota_balance(): void
    {
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser();
        $this->actingAs($admin);

        $response = $this->post(route('cuti.store'), [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'jenis_cuti' => 'Cuti Hari Raya',
            'tanggal_mulai' => Carbon::today()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => Carbon::today()->addDays(4)->format('Y-m-d'),
            'keterangan' => 'Cuti hari raya',
        ]);

        $response->assertRedirect(route('cuti.index'));
        $this->assertDatabaseHas('kuota_cuti_karyawan', [
            'id_karyawan' => $employee['karyawan']->id_karyawan,
            'remaining_quota' => 0,
        ]);
    }

    public function test_contract_employee_cannot_use_annual_leave_by_default(): void
    {
        $this->seedLeaveTypes();
        $admin = $this->createAdminUser();
        $employee = $this->createEmployeeUser('Kontrak');
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

    private function createEmployeeUser(string $statusKaryawan = 'Tetap'): array
    {
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff']);
        $divisi = Divisi::firstOrCreate(['nama_divisi' => 'Divisi Test']);

        $user = User::create([
            'username' => 'employee_' . uniqid(),
            'password' => bcrypt('password'),
            'role' => 'karyawan',
        ]);

        $karyawan = Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Employee Test',
            'id_jabatan' => $jabatan->id,
            'id_divisi' => $divisi->id,
            'tanggal_masuk' => Carbon::today()->format('Y-m-d'),
            'status_aktif' => 'Aktif',
            'status_karyawan' => $statusKaryawan,
        ]);

        app(LeaveQuotaService::class)->ensureBalancesFor($karyawan);

        return ['user' => $user, 'karyawan' => $karyawan];
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
