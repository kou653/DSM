<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('projects');
        $assignedProjectIds = $user->projects->pluck('id');
        $isAdmin = $user->hasRole('admin');

        $projects = Project::query()
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) use ($assignedProjectIds, $isAdmin) {
                $canAccess = $isAdmin || $assignedProjectIds->contains($project->id);

                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'partner_name' => $project->partner_name,
                    'status' => $project->status,
                    'description' => $project->description,
                    'created_at' => $project->created_at,
                    'can_access' => $canAccess,
                ];
            })
            ->values();

        return response()->json([
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:255'],
            'partner_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Projet cree avec succes.',
            'project' => $this->formatProject($project),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $this->ensureProjectAccess($request, $project);

        $project->load(['parcelles', 'users:id,nom_complet,email']);

        return response()->json([
            'project' => [
                ...$this->formatProject($project),
                'parcelles_count' => $project->parcelles->count(),
                'users' => $project->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nom_complet' => $user->nom_complet,
                        'email' => $user->email,
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('projects', 'code')->ignore($project->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'partner_name' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Projet mis a jour avec succes.',
            'project' => $this->formatProject($project->fresh()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project)
    {
        $this->ensureAdmin($request);

        $project->delete();

        return response()->json([
            'message' => 'Projet supprime avec succes.',
        ]);
    }

    public function dashboard(Request $request, Project $project)
    {
        $this->ensureProjectAccess($request, $project);

        $project->load(['users:id,nom_complet,email', 'parcelles.cooperative:id,name,city']);

        $plants = Plant::query()
            ->with([
                'parcelle:id,project_id,code,name,city,gps_lat,gps_long',
                'espece:id,common_name,scientific_name',
                'cooperative:id,name,city',
                'etatSanitaire:id,name',
            ])
            ->whereHas('parcelle', fn ($query) => $query->where('project_id', $project->id))
            ->get();

        $monitorings = Monitoring::query()
            ->with([
                'parcelle:id,project_id,code,name,city,gps_lat,gps_long',
                'espece:id,common_name,scientific_name',
                'user:id,nom_complet,email',
            ])
            ->where('project_id', $project->id)
            ->orderByDesc('monitored_at')
            ->get();

        $totals = [
            'parcelles_count' => $project->parcelles->count(),
            'plants_count' => $plants->count(),
            'monitorings_count' => $monitorings->count(),
            'plants_planted' => $monitorings->sum('plants_planted'),
            'plants_alive' => $monitorings->sum('plants_alive'),
            'plants_dead' => $monitorings->sum('plants_dead'),
        ];

        $totals['survival_rate'] = $totals['plants_planted'] > 0
            ? round(($totals['plants_alive'] / $totals['plants_planted']) * 100, 2)
            : 0;

        $parcelles = $project->parcelles->map(function ($parcelle) use ($monitorings, $plants) {
            $parcellePlants = $plants->where('parcelle_id', $parcelle->id);
            $parcelleMonitorings = $monitorings->where('parcelle_id', $parcelle->id);

            return [
                'id' => $parcelle->id,
                'code' => $parcelle->code,
                'name' => $parcelle->name,
                'city' => $parcelle->city,
                'cooperative' => $parcelle->cooperative?->name,
                'surface_area' => $parcelle->surface_area,
                'gps_lat' => $parcelle->gps_lat,
                'gps_long' => $parcelle->gps_long,
                'plants_count' => $parcellePlants->count(),
                'plants_planted' => $parcelleMonitorings->sum('plants_planted'),
                'plants_alive' => $parcelleMonitorings->sum('plants_alive'),
                'plants_dead' => $parcelleMonitorings->sum('plants_dead'),
            ];
        })->values();

        $speciesBreakdown = $monitorings
            ->groupBy('espece_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'espece_id' => $first->espece_id,
                    'common_name' => $first->espece?->common_name,
                    'scientific_name' => $first->espece?->scientific_name,
                    'plants_planted' => $items->sum('plants_planted'),
                    'plants_alive' => $items->sum('plants_alive'),
                    'plants_dead' => $items->sum('plants_dead'),
                ];
            })
            ->values();

        $latestMonitorings = $monitorings->take(10)->map(function ($monitoring) {
            return [
                'id' => $monitoring->id,
                'monitored_at' => $monitoring->monitored_at,
                'parcelle' => $monitoring->parcelle?->name,
                'espece' => $monitoring->espece?->common_name,
                'plants_planted' => $monitoring->plants_planted,
                'plants_alive' => $monitoring->plants_alive,
                'plants_dead' => $monitoring->plants_dead,
                'mortality_cause' => $monitoring->mortality_cause,
                'user' => $monitoring->user?->nom_complet,
            ];
        })->values();

        $mapPoints = $parcelles
            ->filter(fn ($parcelle) => $parcelle['gps_lat'] !== null && $parcelle['gps_long'] !== null)
            ->values();

        return response()->json([
            'project' => [
                ...$this->formatProject($project),
                'users' => $project->users->map(fn ($user) => [
                    'id' => $user->id,
                    'nom_complet' => $user->nom_complet,
                    'email' => $user->email,
                ])->values(),
            ],
            'totals' => $totals,
            'parcelles' => $parcelles,
            'species_breakdown' => $speciesBreakdown,
            'latest_monitorings' => $latestMonitorings,
            'map_points' => $mapPoints,
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Cette action est reservee a l administrateur.');
    }

    private function ensureProjectAccess(Request $request, Project $project): void
    {
        $user = $request->user()->loadMissing('projects');

        abort_unless(
            $user->hasRole('admin') || $user->projects->contains('id', $project->id),
            403,
            'Vous n avez pas acces a ce projet.'
        );
    }

    private function formatProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'code' => $project->code,
            'name' => $project->name,
            'partner_name' => $project->partner_name,
            'status' => $project->status,
            'description' => $project->description,
            'created_at' => $project->created_at,
        ];
    }
}
