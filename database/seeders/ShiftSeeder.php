<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['kode_shift' => 'P', 'nama_shift' => 'Pagi', 'jam_masuk' => '08:00:00', 'jam_pulang' => '17:00:00'],
            ['kode_shift' => 'M', 'nama_shift' => 'Middle', 'jam_masuk' => '11:00:00', 'jam_pulang' => '20:00:00'],
            ['kode_shift' => 'S', 'nama_shift' => 'Siang', 'jam_masuk' => '13:00:00', 'jam_pulang' => '22:00:00'],
            ['kode_shift' => 'L', 'nama_shift' => 'Libur', 'jam_masuk' => null, 'jam_pulang' => null],
            ['kode_shift' => 'C', 'nama_shift' => 'Cuti', 'jam_masuk' => null, 'jam_pulang' => null],
            ['kode_shift' => 'H', 'nama_shift' => 'Hadir', 'jam_masuk' => null, 'jam_pulang' => null],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['kode_shift' => $shift['kode_shift']],
                $shift
            );
        }
    }
}
