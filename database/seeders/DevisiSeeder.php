<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Divisi;

class DevisiSeeder extends Seeder
{
    public function run(): void
    {
        $divisis = [
            'NBCS',
            'NSC1',
            'NSC2',
            'Office',
        ];

        foreach ($divisis as $divisi) {
            Divisi::firstOrCreate(['nama_divisi' => $divisi]);
        }
    }
}
