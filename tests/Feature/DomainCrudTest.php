<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Espece;
use App\Models\Parcelle;
use App\Models\Plant;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DomainCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_especes(): void
    {
        $admin = User::factory()->create(['role' => 'administrateur']);
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/especes', [
            'nom_commun' => 'Acacia',
            'nom_scientifique' => 'Acacia mangium',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('espece.nom_commun', 'Acacia');

        $especeId = $createResponse->json('espece.id');

        $this->putJson("/api/especes/{$especeId}", [
            'nom_commun' => 'Acacia Modifie',
        ])->assertOk()
            ->assertJsonPath('espece.nom_commun', 'Acacia Modifie');

        $this->deleteJson("/api/especes/{$especeId}")
            ->assertOk();

        $this->assertDatabaseMissing('especes', ['id' => $especeId]);
    }

    public function test_admin_can_crud_cooperatives(): void
    {
        $admin = User::factory()->create(['role' => 'administrateur']);
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/cooperatives', [
            'nom' => 'Coop Centrale',
            'entreprise' => 'Entreprise Coop',
            'contact' => 'Mme Doe',
            'email' => 'coop@example.com',
            'ville' => 'Kara',
            'village' => 'Sokode',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('cooperative.nom', 'Coop Centrale');

        $cooperativeId = $createResponse->json('cooperative.id');

        $this->putJson("/api/cooperatives/{$cooperativeId}", [
            'ville' => 'Lome',
        ])->assertOk()
            ->assertJsonPath('cooperative.ville', 'Lome');

        $this->deleteJson("/api/cooperatives/{$cooperativeId}")
            ->assertOk();

        $this->assertDatabaseMissing('cooperatives', ['id' => $cooperativeId]);
    }

    public function test_non_admin_can_list_but_not_mutate_referentials(): void
    {
        $user = User::factory()->create(['role' => 'agent terrain']);
        Espece::create([
            'nom_commun' => 'Neem',
            'nom_scientifique' => 'Azadirachta indica',
        ]);
        Cooperative::create([
            'nom' => 'Coop Lecture',
            'entreprise' => 'Entreprise',
            'contact' => 'John Doe',
            'email' => 'lecture@example.com',
            'ville' => 'Kara',
            'village' => 'Village',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/especes')
            ->assertOk()
            ->assertJsonCount(1, 'especes');

        $this->getJson('/api/cooperatives')
            ->assertOk()
            ->assertJsonCount(1, 'cooperatives');

        $this->postJson('/api/especes', [
            'nom_commun' => 'Interdit',
            'nom_scientifique' => 'Interdit',
        ])->assertForbidden();

        $this->postJson('/api/cooperatives', [
            'nom' => 'Interdite',
            'entreprise' => 'Entreprise',
            'contact' => 'Jane Doe',
            'email' => 'interdite@example.com',
            'ville' => 'Kara',
            'village' => 'Village',
        ])->assertForbidden();
    }

    public function test_access_to_parcelles_plants_and_monitoring_is_scoped_to_assigned_projects(): void
    {
        $user = User::factory()->create(['role' => 'agent terrain']);
        $assignedProjet = $this->createProjet('Projet Assigne');
        $blockedProjet = $this->createProjet('Projet Bloque');
        $cooperative = $this->createCooperative();
        $espece = $this->createEspece();

        $assignedParcelle = Parcelle::create([
            'nom' => 'Parcelle Visible',
            'ville' => 'Kara',
            'cooperative_id' => $cooperative->id,
            'projet_id' => $assignedProjet->id,
            'superficie' => 12.5,
            'lat' => 9.5511,
            'lng' => 1.1864,
        ]);

        $blockedParcelle = Parcelle::create([
            'nom' => 'Parcelle Bloquee',
            'ville' => 'Lome',
            'cooperative_id' => $cooperative->id,
            'projet_id' => $blockedProjet->id,
            'superficie' => 8.5,
            'lat' => 6.1319,
            'lng' => 1.2228,
        ]);

        $assignedPlant = Plant::create([
            'espece_id' => $espece->id,
            'parcelle_id' => $assignedParcelle->id,
            'date_plantation' => '2026-03-15',
            'status' => 'vivant',
            'lat' => 9.5511,
            'lng' => 1.1864,
        ]);

        $blockedPlant = Plant::create([
            'espece_id' => $espece->id,
            'parcelle_id' => $blockedParcelle->id,
            'date_plantation' => '2026-03-16',
            'status' => 'vivant',
            'lat' => 6.1319,
            'lng' => 1.2228,
        ]);

        $user->projects()->attach($assignedProjet);

        Sanctum::actingAs($user);

        $this->getJson('/api/parcelles')
            ->assertOk()
            ->assertJsonCount(1, 'parcelles')
            ->assertJsonPath('parcelles.0.id', $assignedParcelle->id);

        $this->getJson("/api/parcelles/{$assignedParcelle->id}")
            ->assertOk();

        $this->getJson("/api/parcelles/{$blockedParcelle->id}")
            ->assertForbidden();

        $this->getJson("/api/parcelles/{$assignedParcelle->id}/plants")
            ->assertOk()
            ->assertJsonCount(1, 'plants')
            ->assertJsonPath('plants.0.id', $assignedPlant->id);

        $this->getJson("/api/parcelles/{$blockedParcelle->id}/plants")
            ->assertForbidden();

        $this->patchJson("/api/plants/{$assignedPlant->id}/status", [
            'status' => 'mort',
        ])->assertOk()
            ->assertJsonPath('plant.status', 'mort');

        $this->patchJson("/api/plants/{$blockedPlant->id}/status", [
            'status' => 'mort',
        ])->assertForbidden();

        $this->getJson("/api/projets/{$assignedProjet->id}/monitoring")
            ->assertOk()
            ->assertJsonPath('projet_id', $assignedProjet->id);

        $this->getJson("/api/projets/{$blockedProjet->id}/monitoring")
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

    private function createCooperative(): Cooperative
    {
        return Cooperative::create([
            'nom' => 'Coop Test',
            'entreprise' => 'Entreprise',
            'contact' => 'Responsable',
            'email' => 'coop-test@example.com',
            'ville' => 'Kara',
            'village' => 'Village',
        ]);
    }

    private function createEspece(): Espece
    {
        return Espece::create([
            'nom_commun' => 'Acacia',
            'nom_scientifique' => 'Acacia auriculiformis',
        ]);
    }
}
