<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Espece;
use App\Models\EtatSanitaire;
use App\Models\Monitoring;
use App\Models\Parcelle;
use App\Models\Plant;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DomainCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_especes(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/especes', [
            'common_name' => 'Acacia',
            'scientific_name' => 'Acacia mangium',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('espece.common_name', 'Acacia');

        $especeId = $createResponse->json('espece.id');

        $this->putJson("/api/especes/{$especeId}", [
            'common_name' => 'Acacia Modifie',
        ])->assertOk()
            ->assertJsonPath('espece.common_name', 'Acacia Modifie');

        $this->deleteJson("/api/especes/{$especeId}")
            ->assertOk();

        $this->assertDatabaseMissing('especes', ['id' => $especeId]);
    }

    public function test_non_admin_can_list_but_not_mutate_especes(): void
    {
        $user = $this->createAgriculteur();
        Espece::create([
            'common_name' => 'Neem',
            'scientific_name' => 'Azadirachta indica',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/especes')
            ->assertOk()
            ->assertJsonCount(1, 'especes');

        $this->postJson('/api/especes', [
            'common_name' => 'Interdit',
            'scientific_name' => 'Interdit scientific',
        ])->assertForbidden();
    }

    public function test_admin_can_crud_parcelles(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $project = $this->createProject('PRJ001');
        $cooperative = $this->createCooperative('Coop A');

        $createResponse = $this->postJson('/api/parcelles', [
            'code' => 'PAR001',
            'project_id' => $project->id,
            'cooperative_id' => $cooperative->id,
            'name' => 'Parcelle 1',
            'city' => 'Kara',
            'surface_area' => 10.5,
            'responsible_name' => 'Responsable A',
            'contact_phone' => '90000000',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('parcelle.code', 'PAR001');

        $parcelleId = $createResponse->json('parcelle.id');

        $this->putJson("/api/parcelles/{$parcelleId}", [
            'name' => 'Parcelle Modifiee',
            'city' => 'Lome',
        ])->assertOk()
            ->assertJsonPath('parcelle.name', 'Parcelle Modifiee')
            ->assertJsonPath('parcelle.city', 'Lome');

        $this->deleteJson("/api/parcelles/{$parcelleId}")
            ->assertOk();

        $this->assertDatabaseMissing('parcelles', ['id' => $parcelleId]);
    }

    public function test_non_admin_only_sees_parcelles_from_assigned_projects(): void
    {
        $user = $this->createAgriculteur();
        $assignedProject = $this->createProject('PRJ001');
        $blockedProject = $this->createProject('PRJ002');
        $cooperative = $this->createCooperative('Coop A');

        $user->projects()->attach($assignedProject);

        $visibleParcelle = Parcelle::create([
            'code' => 'PAR001',
            'project_id' => $assignedProject->id,
            'cooperative_id' => $cooperative->id,
            'name' => 'Visible',
            'city' => 'Kara',
        ]);

        $blockedParcelle = Parcelle::create([
            'code' => 'PAR002',
            'project_id' => $blockedProject->id,
            'cooperative_id' => $cooperative->id,
            'name' => 'Bloquee',
            'city' => 'Lome',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/parcelles')
            ->assertOk()
            ->assertJsonCount(1, 'parcelles')
            ->assertJsonPath('parcelles.0.id', $visibleParcelle->id);

        $this->getJson("/api/parcelles/{$visibleParcelle->id}")
            ->assertOk();

        $this->getJson("/api/parcelles/{$blockedParcelle->id}")
            ->assertForbidden();
    }

    public function test_admin_can_crud_plants(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        [$project, $cooperative, $parcelle, $espece, $etat, $owner] = $this->plantDependencies();

        $createResponse = $this->postJson('/api/plants', [
            'code' => 'PLT001',
            'ville' => 'Kara',
            'parcelle_id' => $parcelle->id,
            'espece_id' => $espece->id,
            'cooperative_id' => $cooperative->id,
            'etat_sanitaire_id' => $etat->id,
            'user_id' => $owner->id,
            'gps_lat' => 9.5511,
            'gps_long' => 1.1864,
            'planted_at' => '2026-03-24',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('plant.code', 'PLT001')
            ->assertJsonPath('plant.ville', 'Kara');

        $plantId = $createResponse->json('plant.id');

        $this->putJson("/api/plants/{$plantId}", [
            'ville' => 'Lome',
            'notes' => 'Suivi ok',
        ])->assertOk()
            ->assertJsonPath('plant.ville', 'Lome')
            ->assertJsonPath('plant.notes', 'Suivi ok');

        $this->deleteJson("/api/plants/{$plantId}")
            ->assertOk();

        $this->assertDatabaseMissing('plants', ['id' => $plantId]);
    }

    public function test_non_admin_only_sees_plants_from_assigned_projects(): void
    {
        $user = $this->createAgriculteur();
        $assignedProject = $this->createProject('PRJ010');
        $blockedProject = $this->createProject('PRJ011');
        $cooperative = $this->createCooperative('Coop B');
        $espece = Espece::create([
            'common_name' => 'Eucalyptus',
            'scientific_name' => 'Eucalyptus grandis',
        ]);

        $visibleParcelle = Parcelle::create([
            'code' => 'PAR010',
            'project_id' => $assignedProject->id,
            'cooperative_id' => $cooperative->id,
            'name' => 'Parcelle Visible',
            'city' => 'Kara',
        ]);

        $blockedParcelle = Parcelle::create([
            'code' => 'PAR011',
            'project_id' => $blockedProject->id,
            'cooperative_id' => $cooperative->id,
            'name' => 'Parcelle Bloquee',
            'city' => 'Lome',
        ]);

        $visiblePlant = Plant::create([
            'code' => 'PLT010',
            'ville' => 'Kara',
            'parcelle_id' => $visibleParcelle->id,
            'espece_id' => $espece->id,
            'cooperative_id' => $cooperative->id,
        ]);

        $blockedPlant = Plant::create([
            'code' => 'PLT011',
            'ville' => 'Lome',
            'parcelle_id' => $blockedParcelle->id,
            'espece_id' => $espece->id,
            'cooperative_id' => $cooperative->id,
        ]);

        $user->projects()->attach($assignedProject);

        Sanctum::actingAs($user);

        $this->getJson('/api/plants')
            ->assertOk()
            ->assertJsonCount(1, 'plants')
            ->assertJsonPath('plants.0.id', $visiblePlant->id);

        $this->getJson("/api/plants/{$visiblePlant->id}")
            ->assertOk();

        $this->getJson("/api/plants/{$blockedPlant->id}")
            ->assertForbidden();
    }

    public function test_admin_can_crud_monitorings(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        [$project, $cooperative, $parcelle, $espece, $etat, $owner] = $this->plantDependencies();

        $createResponse = $this->postJson('/api/monitorings', [
            'project_id' => $project->id,
            'parcelle_id' => $parcelle->id,
            'espece_id' => $espece->id,
            'user_id' => $owner->id,
            'monitored_at' => '2026-03-24',
            'plants_planted' => 100,
            'plants_alive' => 90,
            'plants_dead' => 10,
            'mortality_cause' => 'Stress hydrique',
            'observation' => 'Besoin d arrosage complementaire',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('monitoring.plants_alive', 90)
            ->assertJsonPath('monitoring.mortality_cause', 'Stress hydrique');

        $monitoringId = $createResponse->json('monitoring.id');

        $this->putJson("/api/monitorings/{$monitoringId}", [
            'plants_alive' => 88,
            'plants_dead' => 12,
            'observation' => 'Mise a jour',
        ])->assertOk()
            ->assertJsonPath('monitoring.plants_alive', 88)
            ->assertJsonPath('monitoring.plants_dead', 12);

        $this->deleteJson("/api/monitorings/{$monitoringId}")
            ->assertOk();

        $this->assertDatabaseMissing('monitorings', ['id' => $monitoringId]);
    }

    public function test_non_admin_cannot_manage_monitorings_but_can_view_project_summary_when_assigned(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createAgriculteur();
        Sanctum::actingAs($admin);

        [$project, $cooperative, $parcelle, $espece, $etat, $owner] = $this->plantDependencies();
        $user->projects()->attach($project);

        Monitoring::create([
            'project_id' => $project->id,
            'parcelle_id' => $parcelle->id,
            'espece_id' => $espece->id,
            'user_id' => $owner->id,
            'monitored_at' => '2026-03-24',
            'plants_planted' => 100,
            'plants_alive' => 95,
            'plants_dead' => 5,
            'mortality_cause' => 'Faible pluie',
            'observation' => 'Bonne reprise globale',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/monitorings', [
            'project_id' => $project->id,
            'parcelle_id' => $parcelle->id,
            'espece_id' => $espece->id,
            'monitored_at' => '2026-03-25',
            'plants_planted' => 50,
            'plants_alive' => 45,
            'plants_dead' => 5,
        ])->assertForbidden();

        $this->getJson('/api/monitorings')
            ->assertForbidden();

        $this->getJson("/api/projects/{$project->id}/monitoring-summary")
            ->assertOk()
            ->assertJsonPath('totals.plants_planted', 100)
            ->assertJsonPath('totals.plants_alive', 95)
            ->assertJsonPath('totals.plants_dead', 5)
            ->assertJsonPath('totals.survival_rate', 95)
            ->assertJsonCount(1, 'mortality_documentation');

        $this->getJson("/api/projects/{$project->id}/monitoring-map")
            ->assertOk()
            ->assertJsonPath('project_totals.plants_planted', 100)
            ->assertJsonCount(1, 'by_parcelle')
            ->assertJsonCount(1, 'by_espece');
    }

    public function test_non_assigned_user_cannot_view_monitoring_summary(): void
    {
        $user = $this->createAgriculteur();
        $project = $this->createProject('PRJ200');

        Sanctum::actingAs($user);

        $this->getJson("/api/projects/{$project->id}/monitoring-summary")
            ->assertForbidden();
    }

    public function test_admin_can_crud_cooperatives(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/cooperatives', [
            'name' => 'Coop Centrale',
            'city' => 'Kara',
            'responsible_name' => 'Mme Doe',
            'contact_phone' => '90001111',
            'contact_email' => 'coop@example.com',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('cooperative.name', 'Coop Centrale');

        $cooperativeId = $createResponse->json('cooperative.id');

        $this->putJson("/api/cooperatives/{$cooperativeId}", [
            'city' => 'Lome',
        ])->assertOk()
            ->assertJsonPath('cooperative.city', 'Lome');

        $this->deleteJson("/api/cooperatives/{$cooperativeId}")
            ->assertOk();

        $this->assertDatabaseMissing('cooperatives', ['id' => $cooperativeId]);
    }

    public function test_non_admin_can_list_but_not_mutate_cooperatives(): void
    {
        $user = $this->createAgriculteur();
        $this->createCooperative('Coop Lecture');

        Sanctum::actingAs($user);

        $this->getJson('/api/cooperatives')
            ->assertOk()
            ->assertJsonCount(1, 'cooperatives');

        $this->postJson('/api/cooperatives', [
            'name' => 'Interdite',
            'city' => 'Kara',
        ])->assertForbidden();
    }

    public function test_project_dashboard_returns_consolidated_payload(): void
    {
        $user = $this->createAgriculteur();
        [$project, $cooperative, $parcelle, $espece, $etat, $owner] = $this->plantDependencies();
        $user->projects()->attach($project);

        $parcelle->update([
            'gps_lat' => 9.55,
            'gps_long' => 1.18,
        ]);

        Plant::create([
            'code' => 'PLT200',
            'ville' => 'Kara',
            'parcelle_id' => $parcelle->id,
            'espece_id' => $espece->id,
            'cooperative_id' => $cooperative->id,
            'etat_sanitaire_id' => $etat->id,
            'user_id' => $owner->id,
            'gps_lat' => 9.55,
            'gps_long' => 1.18,
        ]);

        Monitoring::create([
            'project_id' => $project->id,
            'parcelle_id' => $parcelle->id,
            'espece_id' => $espece->id,
            'user_id' => $owner->id,
            'monitored_at' => '2026-03-24',
            'plants_planted' => 100,
            'plants_alive' => 93,
            'plants_dead' => 7,
            'mortality_cause' => 'Manque d eau',
            'observation' => 'Suivi stable',
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/projects/{$project->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('project.id', $project->id)
            ->assertJsonPath('totals.parcelles_count', 1)
            ->assertJsonPath('totals.plants_count', 1)
            ->assertJsonPath('totals.monitorings_count', 1)
            ->assertJsonPath('totals.plants_planted', 100)
            ->assertJsonPath('totals.plants_alive', 93)
            ->assertJsonPath('totals.plants_dead', 7)
            ->assertJsonCount(1, 'parcelles')
            ->assertJsonCount(1, 'species_breakdown')
            ->assertJsonCount(1, 'latest_monitorings')
            ->assertJsonCount(1, 'map_points');
    }

    private function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agriculteur', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function createAgriculteur(): User
    {
        Role::firstOrCreate(['name' => 'agriculteur', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('agriculteur');

        return $user;
    }

    private function createProject(string $code): Project
    {
        return Project::create([
            'code' => $code,
            'name' => "Projet {$code}",
            'partner_name' => 'Partenaire',
            'status' => 'active',
        ]);
    }

    private function createCooperative(string $name): Cooperative
    {
        return Cooperative::create([
            'name' => $name,
            'city' => 'Kara',
        ]);
    }

    private function plantDependencies(): array
    {
        $project = $this->createProject('PRJ100');
        $cooperative = $this->createCooperative('Coop Plant');
        $parcelle = Parcelle::create([
            'code' => 'PAR100',
            'project_id' => $project->id,
            'cooperative_id' => $cooperative->id,
            'name' => 'Parcelle Plant',
            'city' => 'Kara',
        ]);
        $espece = Espece::create([
            'common_name' => 'Acacia',
            'scientific_name' => 'Acacia auriculiformis',
        ]);
        $etat = EtatSanitaire::create([
            'name' => 'Bon',
        ]);
        $owner = User::factory()->create();

        return [$project, $cooperative, $parcelle, $espece, $etat, $owner];
    }
}
