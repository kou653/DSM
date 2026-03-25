<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('projects');
        $assignedProjectIds = $user->projects->pluck('id');
        $isAdmin = $user->hasRole('admin');

        $query = Plant::query()
            ->with([
                'parcelle:id,project_id,code,name,city',
                'parcelle.project:id,code,name',
                'espece:id,common_name,scientific_name',
                'cooperative:id,name,city',
                'user:id,nom_complet,email',
                'etatSanitaire:id,name',
            ])
            ->join('parcelles', 'parcelles.id', '=', 'plants.parcelle_id')
            ->select('plants.*')
            ->orderBy('plants.code');

        if ($request->filled('project_id')) {
            $query->where('parcelles.project_id', $request->integer('project_id'));
        }

        if ($request->filled('parcelle_id')) {
            $query->where('plants.parcelle_id', $request->integer('parcelle_id'));
        }

        if (!$isAdmin) {
            $query->whereIn('parcelles.project_id', $assignedProjectIds);
        }

        $plants = $query->get()->map(fn (Plant $plant) => $this->formatPlant($plant));

        return response()->json([
            'plants' => $plants,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:plants,code'],
            'ville' => ['required', 'string', 'max:255'],
            'parcelle_id' => ['required', 'integer', 'exists:parcelles,id'],
            'espece_id' => ['required', 'integer', 'exists:especes,id'],
            'cooperative_id' => ['required', 'integer', 'exists:cooperatives,id'],
            'etat_sanitaire_id' => ['nullable', 'integer', 'exists:etat_sanitaires,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_long' => ['nullable', 'numeric'],
            'planted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $plant = Plant::create($validated);
        $plant->load([
            'parcelle:id,project_id,code,name,city',
            'parcelle.project:id,code,name',
            'espece:id,common_name,scientific_name',
            'cooperative:id,name,city',
            'user:id,nom_complet,email',
            'etatSanitaire:id,name',
        ]);

        return response()->json([
            'message' => 'Plant cree avec succes.',
            'plant' => $this->formatPlant($plant),
        ], 201);
    }

    public function show(Request $request, Plant $plant)
    {
        $plant->load([
            'parcelle:id,project_id,code,name,city',
            'parcelle.project:id,code,name',
            'espece:id,common_name,scientific_name',
            'cooperative:id,name,city',
            'user:id,nom_complet,email',
            'etatSanitaire:id,name',
        ]);

        $this->ensureCanAccessProject($request, $plant->parcelle->project_id);

        return response()->json([
            'plant' => $this->formatPlant($plant),
        ]);
    }

    public function update(Request $request, Plant $plant)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('plants', 'code')->ignore($plant->id)],
            'ville' => ['sometimes', 'string', 'max:255'],
            'parcelle_id' => ['sometimes', 'integer', 'exists:parcelles,id'],
            'espece_id' => ['sometimes', 'integer', 'exists:especes,id'],
            'cooperative_id' => ['sometimes', 'integer', 'exists:cooperatives,id'],
            'etat_sanitaire_id' => ['nullable', 'integer', 'exists:etat_sanitaires,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_long' => ['nullable', 'numeric'],
            'planted_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $plant->update($validated);
        $plant->load([
            'parcelle:id,project_id,code,name,city',
            'parcelle.project:id,code,name',
            'espece:id,common_name,scientific_name',
            'cooperative:id,name,city',
            'user:id,nom_complet,email',
            'etatSanitaire:id,name',
        ]);

        return response()->json([
            'message' => 'Plant mis a jour avec succes.',
            'plant' => $this->formatPlant($plant),
        ]);
    }

    public function destroy(Request $request, Plant $plant)
    {
        $this->ensureAdmin($request);

        $plant->delete();

        return response()->json([
            'message' => 'Plant supprime avec succes.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Cette action est reservee a l administrateur.');
    }

    private function ensureCanAccessProject(Request $request, int $projectId): void
    {
        $user = $request->user()->loadMissing('projects');

        abort_unless(
            $user->hasRole('admin') || $user->projects->contains('id', $projectId),
            403,
            'Vous n avez pas acces a cette ressource.'
        );
    }

    private function formatPlant(Plant $plant): array
    {
        return [
            'id' => $plant->id,
            'code' => $plant->code,
            'ville' => $plant->ville,
            'parcelle_id' => $plant->parcelle_id,
            'parcelle' => $plant->parcelle ? [
                'id' => $plant->parcelle->id,
                'project_id' => $plant->parcelle->project_id,
                'code' => $plant->parcelle->code,
                'name' => $plant->parcelle->name,
                'city' => $plant->parcelle->city,
            ] : null,
            'espece_id' => $plant->espece_id,
            'espece' => $plant->espece ? [
                'id' => $plant->espece->id,
                'common_name' => $plant->espece->common_name,
                'scientific_name' => $plant->espece->scientific_name,
            ] : null,
            'cooperative_id' => $plant->cooperative_id,
            'cooperative' => $plant->cooperative ? [
                'id' => $plant->cooperative->id,
                'name' => $plant->cooperative->name,
                'city' => $plant->cooperative->city,
            ] : null,
            'etat_sanitaire_id' => $plant->etat_sanitaire_id,
            'etat_sanitaire' => $plant->etatSanitaire?->name,
            'user_id' => $plant->user_id,
            'user' => $plant->user ? [
                'id' => $plant->user->id,
                'nom_complet' => $plant->user->nom_complet,
                'email' => $plant->user->email,
            ] : null,
            'gps_lat' => $plant->gps_lat,
            'gps_long' => $plant->gps_long,
            'planted_at' => $plant->planted_at,
            'notes' => $plant->notes,
            'created_at' => $plant->created_at,
        ];
    }
}
