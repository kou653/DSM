<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier si les rôles existent déjà pour éviter les doublons
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['statut' => 'admin', 'description' => 'Administrateur Principal']);
        $agriRole = Role::firstOrCreate(['name' => 'agriculteur'], ['statut' => 'agriculteur', 'description' => 'Agriculteur de terrain']);
        $commandRole = Role::firstOrCreate(['name' => 'commanditaire'], ['statut' => 'commanditaire', 'description' => 'Commanditaire ou Sponsor']);

        // Créer l'utilisateur Administrateur par défaut
        $adminUser = User::firstOrCreate(
            ['EMAIL' => 'admin@plateforme.com'],
            [
                'NOM COMPLET' => 'Super Admin',
                'CODE_ACCES' => 'ADMIN001',
                'password' => Hash::make('password') // Mot de passe par défaut
            ]
        );

        // Lui assigner le rôle Admin s'il ne l'a pas déjà
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole($adminRole);
        }
    }
}
