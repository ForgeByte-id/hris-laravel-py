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
     * - hr: HR department access (can view all attendance, approve leave, etc.)
     * - employee: Regular employee (restricted to own data)
     */
    public function run(): void
    {
        // Create roles with guard 'web'
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    }
}
