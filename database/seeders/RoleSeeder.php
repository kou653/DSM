<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@plateforme.com'],
            [
                'nom_complet' => 'Super Admin',
                'code_acces' => 'ADMIN001',
                'password' => Hash::make('password'),
            ]
        );

        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }
    }
}
