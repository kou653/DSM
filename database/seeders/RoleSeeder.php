<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'administrateur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agent terrain', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'commanditaire', 'guard_name' => 'web']);
    }
}
