<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Setup roles & jabatan & devisi dulu
        $this->call(RolePermissionSeeder::class);
        $this->call(MenuItemSeeder::class);
        $this->call(JabatanSeeder::class);
        $this->call(DevisiSeeder::class);
        $this->call(ShiftSeeder::class);
        $this->call(LeaveTypeSeeder::class);

        // ========================
        // ADMIN
        // ========================
        $admin = User::updateOrCreate([
            'username' => 'admin',
        ], [
            'email' => 'admin@hris.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->call(RealEmployeeSeeder::class);
    }
}
