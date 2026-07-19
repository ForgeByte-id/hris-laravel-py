<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class ManagementDummySeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('Management', 'web');

        $user = User::updateOrCreate(
            ['username' => 'management_dummy'],
            ['password' => bcrypt('password123'), 'role' => 'Management']
        );

        $user->assignRole('Management');
    }
}
