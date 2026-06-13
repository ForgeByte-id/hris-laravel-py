<?php

namespace Database\Seeders;

use App\Models\Devisi;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use App\Services\LeaveQuotaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RealEmployeeSeeder extends Seeder
{
    private const DIVISION_DEFAULT_SHIFT = [
        'NBCS' => 'P',
        'Office' => 'P',
        'NSC1' => 'P',
        'NSC2' => 'P',
    ];

    private const DIVISION_HEAD_POSITIONS = [
        'Manager Divisi',
        'Wakil Manager Divisi',
        'Manager Umum',
        'Wakil Manager Umum',
        'Supervisor',
    ];

    public function run(): void
    {
        $defaultPassword = (string) config('hris.default_import_password');

        if ($defaultPassword === '') {
            throw new RuntimeException('HRIS_DEFAULT_IMPORT_PASSWORD wajib diisi sebelum menjalankan RealEmployeeSeeder.');
        }

        DB::transaction(function () use ($defaultPassword) {
            $this->ensureMasterData();

            foreach ($this->employees() as $employee) {
                $this->seedEmployee($employee, $defaultPassword);
            }
        });
    }

    /**
     * @param array{name:string,division:string,position:string,start_date:string,active_status:string,employee_status:string} $employee
     */
    private function seedEmployee(array $employee, string $defaultPassword): void
    {
        $division = Devisi::firstOrCreate(['nama_devisi' => $employee['division']]);
        $position = Jabatan::firstOrCreate(['nama_jabatan' => $employee['position']]);
        $shiftCode = self::DIVISION_DEFAULT_SHIFT[$employee['division']] ?? 'P';
        $shift = Shift::where('kode_shift', $shiftCode)->firstOrFail();
        $username = $this->usernameFromName($employee['name']);
        $email = $username . '@hris.local';

        $karyawan = Karyawan::where('nama', $employee['name'])->first();
        $user = User::where('username', $username)
            ->orWhere('email', $email)
            ->when($karyawan?->id_user, fn ($query) => $query->orWhere('id_user', $karyawan->id_user))
            ->first();

        if (!$user) {
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'password' => $defaultPassword,
                'role' => 'karyawan',
            ]);
        } else {
            $user->update([
                'username' => $username,
                'email' => $email,
                'role' => $user->role ?: 'karyawan',
            ]);
        }

        if (!$user->hasRole('karyawan')) {
            $user->assignRole('karyawan');
        }

        if (in_array($employee['position'], self::DIVISION_HEAD_POSITIONS, true) && !$user->hasRole('atasan_divisi')) {
            $user->assignRole('atasan_divisi');
        }

        $annualQuota = $employee['employee_status'] === 'Tetap' ? 12 : 0;

        $payload = [
            'id_user' => $user->id_user,
            'nama' => $employee['name'],
            'id_devisi' => $division->id,
            'id_jabatan' => $position->id,
            'kode_shift' => $shift->kode_shift,
            'tanggal_masuk' => $employee['start_date'],
            'tanggal_mulai_kerja' => $employee['start_date'],
            'status_aktif' => $employee['active_status'],
            'status_karyawan' => $employee['employee_status'],
            'yearly_leave_quota' => $annualQuota,
            'remaining_leave_quota' => $annualQuota,
        ];

        if ($karyawan) {
            $karyawan->update($payload);
            app(LeaveQuotaService::class)->ensureBalancesFor($karyawan->refresh());
            return;
        }

        $karyawan = Karyawan::updateOrCreate(
            ['id_user' => $user->id_user],
            $payload
        );

        app(LeaveQuotaService::class)->ensureBalancesFor($karyawan);
    }

    private function ensureMasterData(): void
    {
        foreach (array_unique(array_column($this->employees(), 'division')) as $division) {
            Devisi::firstOrCreate(['nama_devisi' => $division]);
        }

        foreach (array_unique(array_column($this->employees(), 'position')) as $position) {
            Jabatan::firstOrCreate(['nama_jabatan' => $position]);
        }
    }

    private function usernameFromName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', '.')
            ->trim('.')
            ->value();
    }

    /**
     * @return array<int, array{name:string,division:string,position:string,start_date:string,active_status:string,employee_status:string}>
     */
    private function employees(): array
    {
        return [
            ['name' => 'Made Widiantara', 'division' => 'NBCS', 'position' => 'Wakil Manager Divisi', 'start_date' => '2014-07-07', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Ni Wayan Marsiningsih', 'division' => 'NBCS', 'position' => 'Kasir', 'start_date' => '2014-11-27', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Gede Juli Suparwata', 'division' => 'NBCS', 'position' => 'Manager Divisi', 'start_date' => '2016-08-16', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Nengah Wardika', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2017-11-27', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Wayan Ratnata Jaya', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2017-12-07', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Apolonaris Antariksa', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2019-07-08', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Nengah Budiastawan', 'division' => 'NBCS', 'position' => 'Manager Divisi', 'start_date' => '2019-09-30', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Ni Nengah Nastiti Dwiarini', 'division' => 'NBCS', 'position' => 'Kasir', 'start_date' => '2023-02-28', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Teoktista Dilma', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2023-08-08', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Nengah Adiek Yudiarta', 'division' => 'NBCS', 'position' => 'Wakil Manager Divisi', 'start_date' => '2023-09-08', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Roni Gunawan Saputra', 'division' => 'NBCS', 'position' => 'Kasir', 'start_date' => '2023-12-22', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Fadiyah Rahma Dina', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2025-02-14', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Dewi Alpianti', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2025-09-11', 'active_status' => 'Aktif', 'employee_status' => 'Kontrak'],
            ['name' => 'Citra BR Sinuraya', 'division' => 'NBCS', 'position' => 'Kasir', 'start_date' => '2026-03-23', 'active_status' => 'Aktif', 'employee_status' => 'Training'],
            ['name' => 'Anak Agung Istri Dwi Sanishca Kusuma Dalem', 'division' => 'NBCS', 'position' => 'Customer Service', 'start_date' => '2026-05-08', 'active_status' => 'Aktif', 'employee_status' => 'Training'],
            ['name' => 'I Made Nesa Antara', 'division' => 'NSC2', 'position' => 'Manager Divisi', 'start_date' => '2011-03-18', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Andris Styawan Putro', 'division' => 'NSC2', 'position' => 'Teknisi', 'start_date' => '2011-09-18', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Made Yos Kembariana', 'division' => 'NSC2', 'position' => 'Teknisi', 'start_date' => '2013-12-13', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Suwarno', 'division' => 'NSC2', 'position' => 'Supervisor', 'start_date' => '2014-06-10', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Moch Dimas Aji Ongki Ananda', 'division' => 'NSC2', 'position' => 'Supervisor', 'start_date' => '2019-07-11', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Nengah Mertha Yasa', 'division' => 'NSC1', 'position' => 'Manager Divisi', 'start_date' => '2019-08-26', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Victor Johan Dwi Ariyanto', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2022-10-26', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Kadek Boby Sugiarta', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2023-06-22', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Pande Kadek Mahendra Putra', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2023-06-25', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Ali Romdhon', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2023-08-04', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Faisal Ardiansyah', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2023-11-08', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Wayan Pande Ryoga Rasma Putra', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2024-07-01', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Jayadi Eka Suhandar', 'division' => 'NSC1', 'position' => 'Teknisi', 'start_date' => '2025-07-21', 'active_status' => 'Aktif', 'employee_status' => 'Kontrak'],
            ['name' => 'Erric Parwanto Biu', 'division' => 'NSC1', 'position' => 'Kasir', 'start_date' => '2025-07-23', 'active_status' => 'Aktif', 'employee_status' => 'Kontrak'],
            ['name' => 'I Putu Raka Darmadi', 'division' => 'Office', 'position' => 'Wakil Manager Umum', 'start_date' => '2016-11-30', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Ni Nengah Wiratni', 'division' => 'Office', 'position' => 'Accounting', 'start_date' => '2018-11-24', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Kadek Prima Hariyasa', 'division' => 'Office', 'position' => 'Manager Divisi', 'start_date' => '2019-07-11', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Ketut Paduary Karmanta', 'division' => 'Office', 'position' => 'SDM', 'start_date' => '2023-11-13', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'I Nengah Subawa Kardika Putra', 'division' => 'Office', 'position' => 'Manager Umum', 'start_date' => '2024-01-02', 'active_status' => 'Aktif', 'employee_status' => 'Tetap'],
            ['name' => 'Pande Putu Intan Amelia', 'division' => 'Office', 'position' => 'Online Marketing', 'start_date' => '2026-06-08', 'active_status' => 'Aktif', 'employee_status' => 'Training'],
        ];
    }
}
