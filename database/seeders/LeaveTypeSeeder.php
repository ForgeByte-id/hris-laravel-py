<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            ['nama_cuti' => 'Cuti Tahunan', 'default_quota' => 12, 'applies_to_status' => 'Tetap'],
            ['nama_cuti' => 'Cuti Hari Raya', 'default_quota' => 4, 'applies_to_status' => null],
            ['nama_cuti' => 'Cuti Sakit', 'default_quota' => 6, 'applies_to_status' => null],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                ['nama_cuti' => $leaveType['nama_cuti']],
                $leaveType + ['is_active' => true]
            );
        }
    }
}
