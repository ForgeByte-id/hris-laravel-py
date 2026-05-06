<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Jabatan>
 */
class JabatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = [
            'Staff',
            'Supervisor',
            'Manager',
            'Senior Manager',
            'Director',
            'Head of Department',
            'Consultant',
            'Analyst',
            'Developer',
            'Designer',
        ];

        return [
            'nama_jabatan' => fake()->unique()->randomElement($positions),
        ];
    }
}
