<?php

namespace Database\Seeders;

use App\Models\TipeCuti;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            ['nama_cuti' => 'Cuti Tahunan', 'kuota_cuti' => 12, 'berlaku_untuk_status' => 'Tetap'],
            ['nama_cuti' => 'Cuti Hari Raya', 'kuota_cuti' => 4, 'berlaku_untuk_status' => null],
            ['nama_cuti' => 'Cuti Sakit', 'kuota_cuti' => 6, 'berlaku_untuk_status' => null],
        ];

        foreach ($leaveTypes as $leaveType) {
            TipeCuti::updateOrCreate(
                ['nama_cuti' => $leaveType['nama_cuti']],
                $leaveType + ['is_active' => true]
            );
        }
    }
}
