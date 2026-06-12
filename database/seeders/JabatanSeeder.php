<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
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
            'Manager Divisi',
            'Wakil Manager Divisi',
            'Kasir',
            'Customer Service',
            'Teknisi',
            'Wakil Manager Umum',
            'Accounting',
            'SDM',
            'Manager Umum',
            'Online Marketing',
        ];

        foreach ($jabatans as $jabatan) {
            Jabatan::firstOrCreate([
                'nama_jabatan' => $jabatan
            ]);
        }
    }
}
