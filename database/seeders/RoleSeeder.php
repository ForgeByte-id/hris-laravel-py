<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Establishes the core system roles:
     * - admin: Full system access
     * - hr/hrd: HR department read-only access
     * - atasan_divisi: Division head approval access for same division
     * - manager/supervisor: Legacy compatibility roles
     * - karyawan/employee: Regular employee compatibility roles
     */
    public function run(): void
    {
        // Create roles with guard 'web'
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'hrd', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'SDM', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'atasan_divisi', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }
}
