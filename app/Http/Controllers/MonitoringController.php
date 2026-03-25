<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\Project;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $query = Monitoring::query()
            ->with([
                'project:id,code,name',
                'parcelle:id,project_id,code,name,city',
                'espece:id,common_name,scientific_name',
                'user:id,nom_complet,email',
            ])
            ->orderByDesc('monitored_at');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('parcelle_id')) {
            $query->where('parcelle_id', $request->integer('parcelle_id'));
        }

        if ($request->filled('espece_id')) {
            $query->where('espece_id', $request->integer('espece_id'));
        }

        return response()->json([
            'monitorings' => $query->get()->map(fn (Monitoring $monitoring) => $this->formatMonitoring($monitoring)),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $this->validateMonitoring($request);
        $validated['user_id'] = $validated['user_id'] ?? $request->user()->id;

        $monitoring = Monitoring::create($validated);
        $monitoring->load([
            'project:id,code,name',
            'parcelle:id,project_id,code,name,city',
            'espece:id,common_name,scientific_name',
            'user:id,nom_complet,email',
        ]);

        return response()->json([
            'message' => 'Monitoring cree avec succes.',
            'monitoring' => $this->formatMonitoring($monitoring),
        ], 201);
    }

    public function show(Request $request, Monitoring $monitoring)
    {
        $this->ensureAdmin($request);

        $monitoring->load([
            'project:id,code,name',
            'parcelle:id,project_id,code,name,city',
            'espece:id,common_name,scientific_name',
            'user:id,nom_complet,email',
        ]);

        return response()->json([
            'monitoring' => $this->formatMonitoring($monitoring),
        ]);
    }

    public function update(Request $request, Monitoring $monitoring)
    {
        $this->ensureAdmin($request);

        $validated = $this->validateMonitoring($request, true);
        $monitoring->update($validated);
        $monitoring->load([
            'project:id,code,name',
            'parcelle:id,project_id,code,name,city',
            'espece:id,common_name,scientific_name',
            'user:id,nom_complet,email',
        ]);

        return response()->json([
            'message' => 'Monitoring mis a jour avec succes.',
            'monitoring' => $this->formatMonitoring($monitoring),
        ]);
    }

    public function destroy(Request $request, Monitoring $monitoring)
    {
        $this->ensureAdmin($request);

        $monitoring->delete();

        return response()->json([
            'message' => 'Monitoring supprime avec succes.',
        ]);
    }

    public function projectSummary(Request $request, Project $project)
    {
        $this->ensureProjectAccess($request, $project);

        $monitorings = Monitoring::query()
            ->with([
                'parcelle:id,project_id,code,name,city',
                'espece:id,common_name,scientific_name',
            ])
            ->where('project_id', $project->id)
            ->orderBy('monitored_at')
            ->get();

        $totals = [
            'plants_planted' => $monitorings->sum('plants_planted'),
            'plants_alive' => $monitorings->sum('plants_alive'),
            'plants_dead' => $monitorings->sum('plants_dead'),
        ];

        $survivalRate = $totals['plants_planted'] > 0
            ? (float) round(($totals['plants_alive'] / $totals['plants_planted']) * 100, 2)
            : 0.0;

        $timeline = $monitorings
            ->groupBy('monitored_at')
            ->map(fn ($items, $date) => [
                'date' => $date,
                'plants_planted' => $items->sum('plants_planted'),
                'plants_alive' => $items->sum('plants_alive'),
                'plants_dead' => $items->sum('plants_dead'),
            ])
            ->values();

        $mortalityDocumentation = $monitorings
            ->filter(fn (Monitoring $monitoring) => !empty($monitoring->mortality_cause))
            ->sortByDesc('monitored_at')
            ->values()
            ->map(fn (Monitoring $monitoring) => [
                'id' => $monitoring->id,
                'parcelle' => $monitoring->parcelle?->name,
                'espece' => $monitoring->espece?->common_name,
                'monitored_at' => $monitoring->monitored_at,
                'plants_dead' => $monitoring->plants_dead,
                'mortality_cause' => $monitoring->mortality_cause,
                'observation' => $monitoring->observation,
            ]);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
            ],
            'totals' => [
                ...$totals,
                'survival_rate' => $survivalRate,
            ],
            'timeline' => $timeline,
            'mortality_documentation' => $mortalityDocumentation,
        ]);
    }

    public function mapSummary(Request $request, Project $project)
    {
        $this->ensureProjectAccess($request, $project);

        $monitorings = Monitoring::query()
            ->with([
                'parcelle:id,project_id,code,name,city,gps_lat,gps_long',
                'espece:id,common_name,scientific_name',
            ])
            ->where('project_id', $project->id)
            ->get();

        $projectTotals = [
            'plants_planted' => $monitorings->sum('plants_planted'),
            'plants_alive' => $monitorings->sum('plants_alive'),
            'plants_dead' => $monitorings->sum('plants_dead'),
        ];

        $byParcelle = $monitorings
            ->groupBy('parcelle_id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'parcelle_id' => $first->parcelle_id,
                    'parcelle_code' => $first->parcelle?->code,
                    'parcelle_name' => $first->parcelle?->name,
                    'city' => $first->parcelle?->city,
                    'gps_lat' => $first->parcelle?->gps_lat,
                    'gps_long' => $first->parcelle?->gps_long,
                    'plants_planted' => $items->sum('plants_planted'),
                    'plants_alive' => $items->sum('plants_alive'),
                    'plants_dead' => $items->sum('plants_dead'),
                ];
            })
            ->values();

        $byEspece = $monitorings
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

        return response()->json([
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
            ],
            'project_totals' => $projectTotals,
            'by_parcelle' => $byParcelle,
            'by_espece' => $byEspece,
        ]);
    }

    private function validateMonitoring(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'project_id' => [$required, 'integer', 'exists:projects,id'],
            'parcelle_id' => [$required, 'integer', 'exists:parcelles,id'],
            'espece_id' => [$required, 'integer', 'exists:especes,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'monitored_at' => [$required, 'date'],
            'plants_planted' => [$required, 'integer', 'min:0'],
            'plants_alive' => [$required, 'integer', 'min:0'],
            'plants_dead' => [$required, 'integer', 'min:0'],
            'mortality_cause' => ['nullable', 'string'],
            'observation' => ['nullable', 'string'],
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

    private function formatMonitoring(Monitoring $monitoring): array
    {
        return [
            'id' => $monitoring->id,
            'project_id' => $monitoring->project_id,
            'project' => $monitoring->project ? [
                'id' => $monitoring->project->id,
                'code' => $monitoring->project->code,
                'name' => $monitoring->project->name,
            ] : null,
            'parcelle_id' => $monitoring->parcelle_id,
            'parcelle' => $monitoring->parcelle ? [
                'id' => $monitoring->parcelle->id,
                'code' => $monitoring->parcelle->code,
                'name' => $monitoring->parcelle->name,
                'city' => $monitoring->parcelle->city,
            ] : null,
            'espece_id' => $monitoring->espece_id,
            'espece' => $monitoring->espece ? [
                'id' => $monitoring->espece->id,
                'common_name' => $monitoring->espece->common_name,
                'scientific_name' => $monitoring->espece->scientific_name,
            ] : null,
            'user_id' => $monitoring->user_id,
            'user' => $monitoring->user ? [
                'id' => $monitoring->user->id,
                'nom_complet' => $monitoring->user->nom_complet,
                'email' => $monitoring->user->email,
            ] : null,
            'monitored_at' => $monitoring->monitored_at,
            'plants_planted' => $monitoring->plants_planted,
            'plants_alive' => $monitoring->plants_alive,
            'plants_dead' => $monitoring->plants_dead,
            'mortality_cause' => $monitoring->mortality_cause,
            'observation' => $monitoring->observation,
            'created_at' => $monitoring->created_at,
        ];
    }
}
