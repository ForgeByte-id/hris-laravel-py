<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Karyawan;
use App\Models\Jabatan;
use App\Models\Devisi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Setup roles & jabatan & devisi dulu
        $this->call(RolePermissionSeeder::class);
        $this->call(JabatanSeeder::class);
        $this->call(DevisiSeeder::class);

        // ========================
        // ADMIN
        // ========================
        $admin = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@hris.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $admin->assignRole('admin');

        // ========================
        // NOVA SUGIANTARA (Specific Employee)
        // ========================
        $novaUser = User::create([
            'username' => 'nova.sugiantara',
            'email' => 'nova@hris.local',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
        ]);

        $novaUser->assignRole('karyawan');

        // Get Devisi IT and Jabatan Developer
        $devisiIT = Devisi::where('nama_devisi', 'IT')->first();
        $jabatanDeveloper = Jabatan::where('nama_jabatan', 'Developer')->first();

        Karyawan::create([
            'id_user' => $novaUser->id_user,
            'nama' => 'Nova Sugiantara',
            'id_jabatan' => $jabatanDeveloper->id ?? 1,
            'id_devisi' => $devisiIT->id ?? 1,
            'tanggal_masuk' => now(),
            'face_embedding' => null,
        ]);

        // ========================
        // KARYAWAN (Random)
        // ========================
        User::factory(5)->create([
            'role' => 'karyawan',
        ])->each(function ($user) {
            $user->assignRole('karyawan');
        });

        // ========================
        // MANAGER (atasan level 1)
        // ========================
        User::factory(2)->create([
            'role' => 'manager',
        ])->each(function ($user) {
            $user->assignRole('manager');
        });

        // ========================
        // SUPERVISOR (atasan level 2)
        // ========================
        User::factory(2)->create([
            'role' => 'supervisor',
        ])->each(function ($user) {
            $user->assignRole('supervisor');
        });
    }
}
