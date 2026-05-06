<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevisiSeeder extends Seeder
{
    public function run(): void
    {

        // disable FK
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // truncate
        DB::table('devisis')->truncate();

        // enable FK kembali
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $devisis = [
            'IT',
            'HR',
            'Finance',
            'Operations',
            'Sales',
            'Marketing',
        ];

        foreach ($devisis as $devisi) {
            DB::table('devisis')->insert([
                'nama_devisi' => $devisi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
