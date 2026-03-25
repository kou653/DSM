<?php

namespace App\Http\Controllers;

use App\Models\Parcelle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParcelleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('projects');
        $assignedProjectIds = $user->projects->pluck('id');
        $isAdmin = $user->hasRole('admin');

        $query = Parcelle::query()
            ->with(['project:id,code,name', 'cooperative:id,name,city'])
            ->orderBy('name');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if (!$isAdmin) {
            $query->whereIn('project_id', $assignedProjectIds);
        }

        $parcelles = $query->get()->map(fn (Parcelle $parcelle) => $this->formatParcelle($parcelle));

        return response()->json([
            'parcelles' => $parcelles,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:parcelles,code'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'cooperative_id' => ['required', 'integer', 'exists:cooperatives,id'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'surface_area' => ['nullable', 'numeric', 'min:0'],
            'surface_unit' => ['nullable', 'string', 'max:20'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_long' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $parcelle = Parcelle::create($validated);
        $parcelle->load(['project:id,code,name', 'cooperative:id,name,city']);

        return response()->json([
            'message' => 'Parcelle creee avec succes.',
            'parcelle' => $this->formatParcelle($parcelle),
        ], 201);
    }

    public function show(Request $request, Parcelle $parcelle)
    {
        $this->ensureCanAccessProject($request, $parcelle->project_id);

        $parcelle->load(['project:id,code,name', 'cooperative:id,name,city']);

        return response()->json([
            'parcelle' => $this->formatParcelle($parcelle),
        ]);
    }

    public function update(Request $request, Parcelle $parcelle)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('parcelles', 'code')->ignore($parcelle->id)],
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'cooperative_id' => ['sometimes', 'integer', 'exists:cooperatives,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'surface_area' => ['nullable', 'numeric', 'min:0'],
            'surface_unit' => ['nullable', 'string', 'max:20'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_long' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $parcelle->update($validated);
        $parcelle->load(['project:id,code,name', 'cooperative:id,name,city']);

        return response()->json([
            'message' => 'Parcelle mise a jour avec succes.',
            'parcelle' => $this->formatParcelle($parcelle),
        ]);
    }

    public function destroy(Request $request, Parcelle $parcelle)
    {
        $this->ensureAdmin($request);

        $parcelle->delete();

        return response()->json([
            'message' => 'Parcelle supprimee avec succes.',
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

    private function formatParcelle(Parcelle $parcelle): array
    {
        return [
            'id' => $parcelle->id,
            'code' => $parcelle->code,
            'project_id' => $parcelle->project_id,
            'project' => $parcelle->project ? [
                'id' => $parcelle->project->id,
                'code' => $parcelle->project->code,
                'name' => $parcelle->project->name,
            ] : null,
            'cooperative_id' => $parcelle->cooperative_id,
            'cooperative' => $parcelle->cooperative ? [
                'id' => $parcelle->cooperative->id,
                'name' => $parcelle->cooperative->name,
                'city' => $parcelle->cooperative->city,
            ] : null,
            'name' => $parcelle->name,
            'city' => $parcelle->city,
            'surface_area' => $parcelle->surface_area,
            'surface_unit' => $parcelle->surface_unit,
            'responsible_name' => $parcelle->responsible_name,
            'contact_phone' => $parcelle->contact_phone,
            'gps_lat' => $parcelle->gps_lat,
            'gps_long' => $parcelle->gps_long,
            'notes' => $parcelle->notes,
            'created_at' => $parcelle->created_at,
        ];
    }
}
