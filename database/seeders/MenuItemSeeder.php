<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // disable FK
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        MenuItem::truncate();

        // enable FK kembali
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $menus = [
            [
                'name' => 'Dashboard',
                'route' => '/dashboard',
                'icon' => 'bi-house-door-fill',
                'order' => 1,
                'is_admin_only' => false,
            ],
            [
                'name' => 'Cuti',
                'route' => '/cuti',
                'icon' => 'bi-calendar-event-fill',
                'order' => 2,
                'is_admin_only' => false,
            ],
            [
                'name' => 'Absensi',
                'route' => '/attendance',
                'icon' => 'bi-camera-video-fill',
                'order' => 3,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Riwayat Absensi',
                'route' => '/attendance/history',
                'icon' => 'bi-clock-history',
                'order' => 4,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Role Management',
                'route' => '/admin/roles',
                'icon' => 'bi-shield-lock-fill',
                'order' => 5,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Hak Akses',
                'route' => '/admin/permissions',
                'icon' => 'bi-lock-fill',
                'order' => 6,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Jadwal Kerja',
                'route' => '/jadwal',
                'icon' => 'bi-clock-fill',
                'order' => 7,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Divisi',
                'route' => '/divisi',
                'icon' => 'bi-diagram-3-fill',
                'order' => 8,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Jabatan',
                'route' => '/jabatan',
                'icon' => 'bi-briefcase-fill',
                'order' => 9,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Karyawan',
                'route' => '/karyawan',
                'icon' => 'bi-people-fill',
                'order' => 10,
                'is_admin_only' => true,
            ],
            [
                'name' => 'Laporan',
                'route' => '/laporan',
                'icon' => 'bi-bar-chart-fill',
                'order' => 11,
                'is_admin_only' => true,
            ],
        ];

        foreach ($menus as $menu) {
            MenuItem::create($menu);
        }
    }
}
