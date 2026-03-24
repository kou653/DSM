<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_assigned_projects(): void
    {
        Role::create(['name' => 'agriculteur', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);
        $user->assignRole('agriculteur');

        $project = Project::create([
            'code' => 'PRJ001',
            'name' => 'Projet Nord',
            'partner_name' => 'Partenaire A',
            'status' => 'active',
        ]);
        $user->projects()->attach($project);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonCount(1, 'user.projects')
            ->assertJsonPath('user.projects.0.id', $project->id);
    }

    public function test_non_admin_can_only_access_assigned_project(): void
    {
        Role::create(['name' => 'agriculteur', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('agriculteur');

        $assignedProject = Project::create([
            'code' => 'PRJ001',
            'name' => 'Projet Assigne',
            'partner_name' => 'Partenaire A',
            'status' => 'active',
        ]);

        $blockedProject = Project::create([
            'code' => 'PRJ002',
            'name' => 'Projet Bloque',
            'partner_name' => 'Partenaire B',
            'status' => 'active',
        ]);

        $user->projects()->attach($assignedProject);

        Sanctum::actingAs($user);

        $this->getJson("/api/projects/{$assignedProject->id}")
            ->assertOk()
            ->assertJsonPath('project.id', $assignedProject->id);

        $this->getJson("/api/projects/{$blockedProject->id}")
            ->assertForbidden();
    }

    public function test_admin_can_access_any_project(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $project = Project::create([
            'code' => 'PRJ001',
            'name' => 'Projet Libre',
            'partner_name' => 'Partenaire A',
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('project.id', $project->id);
    }
}
