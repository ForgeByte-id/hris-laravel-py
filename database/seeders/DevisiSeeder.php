<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Devisi;

class DevisiSeeder extends Seeder
{
    public function run(): void
    {
        $devisis = [
            'IT',
            'HR',
            'Finance',
            'Operations',
            'Sales',
            'Marketing',
            'NBCS',
            'NSC1',
            'NSC2',
            'Office',
        ];

        foreach ($devisis as $devisi) {
            Devisi::firstOrCreate(['nama_devisi' => $devisi]);
        }
    }
}
