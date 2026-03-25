<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_role_and_projects(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'agriculteur', 'guard_name' => 'web']);
        Role::create(['name' => 'commanditaire', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $projectA = Project::create([
            'code' => 'PRJ001',
            'name' => 'Projet A',
            'partner_name' => 'Partenaire A',
            'status' => 'active',
        ]);

        $projectB = Project::create([
            'code' => 'PRJ002',
            'name' => 'Projet B',
            'partner_name' => 'Partenaire B',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'nom_complet' => 'Agent Terrain',
            'email' => 'agent@example.com',
            'code_acces' => 'AGT001',
            'password' => 'secret123',
            'role' => 'agriculteur',
            'project_ids' => [$projectA->id, $projectB->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'agent@example.com')
            ->assertJsonPath('user.statut', 'agriculteur')
            ->assertJsonCount(2, 'user.projects');

        $this->assertDatabaseHas('users', [
            'email' => 'agent@example.com',
            'code_acces' => 'AGT001',
        ]);
    }

    public function test_admin_can_update_user_role_and_assigned_projects(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'agriculteur', 'guard_name' => 'web']);
        Role::create(['name' => 'commanditaire', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('agriculteur');

        $projectA = Project::create([
            'code' => 'PRJ001',
            'name' => 'Projet A',
            'partner_name' => 'Partenaire A',
            'status' => 'active',
        ]);

        $projectB = Project::create([
            'code' => 'PRJ002',
            'name' => 'Projet B',
            'partner_name' => 'Partenaire B',
            'status' => 'draft',
        ]);

        $user->projects()->attach($projectA);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/users/{$user->id}", [
            'nom_complet' => 'Utilisateur Modifie',
            'role' => 'commanditaire',
            'project_ids' => [$projectB->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('user.nom_complet', 'Utilisateur Modifie')
            ->assertJsonPath('user.statut', 'commanditaire')
            ->assertJsonCount(1, 'user.projects')
            ->assertJsonPath('user.projects.0.id', $projectB->id);
    }

    public function test_admin_can_delete_user(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        Role::create(['name' => 'agriculteur', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('agriculteur');

        $managedUser = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/users', [
            'nom_complet' => 'Interdit',
            'email' => 'interdit@example.com',
            'code_acces' => 'INT001',
            'password' => 'secret123',
            'role' => 'agriculteur',
        ])->assertForbidden();

        $this->putJson("/api/users/{$managedUser->id}", [
            'nom_complet' => 'Interdit',
        ])->assertForbidden();

        $this->deleteJson("/api/users/{$managedUser->id}")
            ->assertForbidden();
    }
}
