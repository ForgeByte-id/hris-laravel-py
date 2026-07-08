<?php

namespace Tests\Feature;

use App\Models\Divisi;
use App\Models\JadwalKerja;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use App\Services\JadwalBulkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalBulkRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_range_skips_duplicate_when_overwrite_false_and_updates_when_true(): void
    {
        $division = Divisi::firstOrCreate(['nama_divisi' => 'Divisi Jadwal']);
        $jabatan = Jabatan::firstOrCreate(['nama_jabatan' => 'Staff']);
        $employee = $this->createEmployee($division, $jabatan);
        $shiftPagi = Shift::create(['kode_shift' => 'Pa', 'nama_shift' => 'Pagi', 'jam_masuk' => '08:00:00', 'jam_pulang' => '17:00:00']);
        $shiftSiang = Shift::create(['kode_shift' => 'Si', 'nama_shift' => 'Siang', 'jam_masuk' => '13:00:00', 'jam_pulang' => '22:00:00']);

        JadwalKerja::create([
            'id_karyawan' => $employee->id_karyawan,
            'tanggal' => Carbon::today()->toDateString(),
            'id_shift' => $shiftPagi->kode_shift,
        ]);

        $service = app(JadwalBulkService::class);
        $first = $service->storeRange([
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_selesai' => Carbon::today()->toDateString(),
            'target_type' => 'all',
            'id_shift' => 'Si',
            'overwrite' => false,
        ]);

        $this->assertSame(0, $first['created']);
        $this->assertSame(1, $first['skipped']);
        $this->assertSame(1, JadwalKerja::where('id_karyawan', $employee->id_karyawan)->whereDate('tanggal', Carbon::today())->count());
        $this->assertDatabaseHas('jadwal_kerja', [
            'id_karyawan' => $employee->id_karyawan,
            'id_shift' => 'Pa',
        ]);

        $second = $service->storeRange([
            'tanggal_mulai' => Carbon::today()->toDateString(),
            'tanggal_selesai' => Carbon::today()->toDateString(),
            'target_type' => 'all',
            'id_shift' => 'Si',
            'overwrite' => true,
        ]);

        $this->assertSame(1, $second['updated']);
        $this->assertSame(1, JadwalKerja::where('id_karyawan', $employee->id_karyawan)->whereDate('tanggal', Carbon::today())->count());
        $this->assertDatabaseHas('jadwal_kerja', [
            'id_karyawan' => $employee->id_karyawan,
            'id_shift' => 'Si',
        ]);
    }

    private function createEmployee(Divisi $division, Jabatan $jabatan): Karyawan
    {
        $user = User::create([
            'username' => 'jadwal_employee',
            'password' => 'password',
            'role' => 'karyawan',
        ]);

        return Karyawan::create([
            'id_user' => $user->id_user,
            'nama' => 'Jadwal Employee',
            'id_divisi' => $division->id,
            'id_jabatan' => $jabatan->id,
            'tanggal_masuk' => Carbon::today()->toDateString(),
            'status_aktif' => 'Aktif',
            'status_karyawan' => 'Tetap',
        ]);
    }
}
