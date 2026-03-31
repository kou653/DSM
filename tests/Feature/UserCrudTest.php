<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_user_with_projects(): void
    {
        $admin = User::factory()->create(['role' => 'administrateur']);
        $projetA = $this->createProjet('Projet A');
        $projetB = $this->createProjet('Projet B');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'nom_complet' => 'Agent Terrain',
            'email' => 'agent@example.com',
            'code_acces' => 'AGT001',
            'password' => 'secret123',
            'role' => 'agent terrain',
            'projects' => [$projetA->id, $projetB->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'agent@example.com')
            ->assertJsonPath('user.role', 'agent terrain')
            ->assertJsonCount(2, 'user.projects');

        $userId = $response->json('user.id');

        $this->putJson("/api/users/{$userId}", [
            'nom_complet' => 'Utilisateur Modifie',
            'role' => 'commanditaire',
            'projects' => [$projetB->id],
        ])->assertOk()
            ->assertJsonPath('user.nom_complet', 'Utilisateur Modifie')
            ->assertJsonPath('user.role', 'commanditaire')
            ->assertJsonCount(1, 'user.projects')
            ->assertJsonPath('user.projects.0.id', $projetB->id);

        $this->deleteJson("/api/users/{$userId}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $user = User::factory()->create(['role' => 'agent terrain']);
        $managedUser = User::factory()->create(['role' => 'commanditaire']);

        Sanctum::actingAs($user);

        $this->postJson('/api/users', [
            'nom_complet' => 'Interdit',
            'email' => 'interdit@example.com',
            'password' => 'secret123',
            'role' => 'agent terrain',
        ])->assertForbidden();

        $this->putJson("/api/users/{$managedUser->id}", [
            'nom_complet' => 'Interdit',
        ])->assertForbidden();

        $this->deleteJson("/api/users/{$managedUser->id}")
            ->assertForbidden();
    }

    private function createProjet(string $nom): Projet
    {
        return Projet::create([
            'nom' => $nom,
            'description' => 'Description',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-31',
            'region' => 'Kara',
            'status' => 'actif',
        ]);
    }
}
