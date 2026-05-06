<?php

namespace Database\Factories;

use App\Models\Devisi;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KaryawanFactory extends Factory
{
    public function definition(): array
    {
        return [
            // fallback kalau dipanggil langsung
            'id_user' => User::factory(),

            'nama' => fake('id_ID')->name(),

            'id_jabatan' => Jabatan::inRandomOrder()->value('id'),

            'id_devisi' => Devisi::inRandomOrder()->value('id'),

            'tanggal_masuk' => fake()->dateTimeBetween('-5 years', 'now'),

            'face_embedding' => null,
        ];
    }
}
