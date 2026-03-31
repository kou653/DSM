<?php

namespace Tests\Feature;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_assigned_projects(): void
    {
        $user = User::factory()->create([
            'role' => 'agent terrain',
            'password' => Hash::make('secret123'),
        ]);

        $projet = $this->createProjet('Projet Nord');
        $user->projects()->attach($projet);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonCount(1, 'user.projects')
            ->assertJsonPath('user.projects.0.id', $projet->id)
            ->assertJsonMissingPath('user.code_acces');
    }

    public function test_non_admin_only_sees_assigned_projects(): void
    {
        $user = User::factory()->create(['role' => 'agent terrain']);
        $assigned = $this->createProjet('Projet Assigne');
        $blocked = $this->createProjet('Projet Bloque');

        $user->projects()->attach($assigned);

        Sanctum::actingAs($user);

        $this->getJson('/api/projets')
            ->assertOk()
            ->assertJsonCount(1, 'projets')
            ->assertJsonPath('projets.0.id', $assigned->id);

        $this->getJson("/api/projets/{$assigned->id}")
            ->assertOk()
            ->assertJsonPath('projet.id', $assigned->id);

        $this->getJson("/api/projets/{$blocked->id}")
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_project(): void
    {
        $admin = User::factory()->create(['role' => 'administrateur']);
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/projets', [
            'nom' => 'Projet Test',
            'description' => 'Description initiale',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-31',
            'region' => 'Kara',
            'status' => 'actif',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('projet.nom', 'Projet Test');

        $projetId = $createResponse->json('projet.id');

        $this->putJson("/api/projets/{$projetId}", [
            'nom' => 'Projet Modifie',
            'status' => 'en_pause',
        ])->assertOk()
            ->assertJsonPath('projet.nom', 'Projet Modifie')
            ->assertJsonPath('projet.status', 'en_pause');

        $this->deleteJson("/api/projets/{$projetId}")
            ->assertOk();

        $this->assertDatabaseMissing('projets', [
            'id' => $projetId,
        ]);
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
