<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::firstOrCreate(
            ['email' => 'admin@agronex.id'],
            [
                'name' => 'Agronex Super Admin',
                'username' => 'admin',
                'role' => 'admin',
                'password' => bcrypt('admin123'),
            ]
        );

        $userSumedang = User::firstOrCreate(
            ['email' => 'sumedang@agronex.id'],
            [
                'name' => 'Petani Sumedang',
                'username' => 'usersumedang',
                'role' => 'user',
                'password' => bcrypt('password123'),
            ]
        );

        $this->call(DeviceSeeder::class);
    }
}
