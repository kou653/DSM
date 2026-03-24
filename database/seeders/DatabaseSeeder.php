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
        $this->call(RoleSeeder::class);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'nom_complet' => 'Test User',
                'code_acces' => 'TEST001',
                'password' => 'password',
            ]
        );
    }
}
