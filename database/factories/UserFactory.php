<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $name = fake('id_ID')->name();

        return [
            'username' => Str::of($name)
                ->lower()
                ->replace(' ', '.')
                ->ascii(),

            'password' => static::$password ??= Hash::make('password'),

            // ❗ tidak set role di sini
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($user) {

            if (in_array($user->role, ['karyawan', 'manager', 'supervisor'])) {
                \App\Models\Karyawan::factory()->create([
                    'id_user' => $user->id_user,
                ]);
            }
        });
    }
}
