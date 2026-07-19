<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ========================
        // PERMISSIONS
        // ========================
        $permissions = [
            'view-dashboard',

            'view-karyawan',
            'create-karyawan',
            'edit-karyawan',
            'delete-karyawan',
            'register-face-karyawan',

            'view-attendance',
            'view-attendance-history',
            'record-attendance',

            'view-cuti',
            'create-cuti',
            'view-cuti-history',
            'approve-cuti',
            'reject-cuti',
            'cancel-cuti',

            'view-jadwal',
            'create-jadwal',
            'edit-jadwal',
            'delete-jadwal',
            'bulk-create-jadwal',
            'set-libur-massal',

            'manage-users',
            'manage-roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // ========================
        // ROLES
        // ========================
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $hr = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hrd = Role::firstOrCreate(['name' => 'hrd', 'guard_name' => 'web']);
        $atasanDivisi = Role::firstOrCreate(['name' => 'atasan_divisi', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $karyawan = Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
        $management = Role::firstOrCreate(['name' => 'Management', 'guard_name' => 'web']);

        // ========================
        // ASSIGN PERMISSIONS
        // ========================
        $admin->givePermissionTo($permissions);

        $hrReadonlyPermissions = [
            'view-dashboard',
            'view-karyawan',
            'view-attendance',
            'view-attendance-history',
            'view-cuti',
            'view-cuti-history',
            'view-jadwal',
        ];

        $hr->givePermissionTo($hrReadonlyPermissions);
        $hrd->givePermissionTo($hrReadonlyPermissions);

        $atasanDivisi->givePermissionTo([
            'view-dashboard',
            'view-karyawan',
            'view-cuti',
            'view-cuti-history',
            'approve-cuti',
            'reject-cuti',
            'view-jadwal',
        ]);

        $manager->givePermissionTo([
            'view-dashboard',
            'view-karyawan',
            'view-attendance',
            'view-attendance-history',
            'view-cuti',
            'approve-cuti',
            'reject-cuti',
            'view-cuti-history',
            'view-jadwal',
            'create-jadwal',
            'edit-jadwal',
        ]);

        $supervisor->givePermissionTo([
            'view-dashboard',
            'view-attendance',
            'view-attendance-history',
            'view-cuti',
            'approve-cuti',
            'view-jadwal',
        ]);

        $karyawan->givePermissionTo([
            'view-dashboard',
            'view-attendance',
            'create-cuti',
            'view-cuti-history',
            'view-jadwal',
        ]);

        // Management = top-level approval (Manager Umum's leave approver).
        // Same effective access as admin for cuti approval + full menu visibility.
        $management->givePermissionTo($permissions);
    }
}
