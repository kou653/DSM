<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nomComplet = env('ADMIN_NOM_COMPLET', 'Super Admin');
        $email = env('ADMIN_EMAIL', 'dsm@dronek.com');
        $password = env('ADMIN_PASSWORD', '1234dsm!@dronek');
        $codeAcces = env('ADMIN_CODE_ACCES', 'ADMIN001');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'nom_complet' => $nomComplet,
                'password' => \Hash::make($password),
                'code_acces' => $codeAcces,
                'role' => 'administrateur',
            ]
        );

        $this->command?->info("Admin '{$email}' créé/mis à jour avec succès.");
    }
}
