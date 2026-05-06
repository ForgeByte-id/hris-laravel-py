<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        // disable FK
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // truncate
        Jabatan::truncate();

        // enable FK kembali
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jabatans = [
            'Administrator',
            'Manager HR',
            'Staff HR',
            'Manager IT',
            'Developer',
            'Analyst IT',
            'Director Finance',
            'Accountant',
            'Financial Analyst',
            'Manager Operations',
            'Staff Operations',
            'Manager Sales',
            'Sales Executive',
            'Manager Marketing',
            'Marketing Specialist',
            'Supervisor',
            'Staff General',
        ];

        foreach ($jabatans as $jabatan) {
            Jabatan::create([
                'nama_jabatan' => $jabatan
            ]);
        }
    }
}
