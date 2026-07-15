<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Services\AuthorizationService;
use Illuminate\Database\Seeder;

class BackfillEmployeeRolesSeeder extends Seeder
{
    public function run(): void
    {
        $authService = app(AuthorizationService::class);

        Karyawan::with(['user', 'jabatan'])
            ->whereHas('user')
            ->each(function (Karyawan $karyawan) use ($authService) {
                $roleName = $authService->roleForJabatan($karyawan->jabatan?->nama_jabatan ?? '');
                $karyawan->user->syncRoles([$roleName]);
            });
    }
}
