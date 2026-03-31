<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['statut' => 'admin', 'description' => 'Administrateur principal']
        );

        Role::firstOrCreate(
            ['name' => 'agriculteur'],
            ['statut' => 'agriculteur', 'description' => 'Agriculteur de terrain']
        );

        Role::firstOrCreate(
            ['name' => 'commanditaire'],
            ['statut' => 'commanditaire', 'description' => 'Commanditaire ou sponsor']
        );
    }
}
