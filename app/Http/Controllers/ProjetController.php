<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use Illuminate\Http\Request;

class ProjetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Projet::query()->withCount('parcelles');

        if ($user->role !== 'administrateur') {
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
        }

        return response()->json([
            'projets' => $query->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Projet::class);

        $validated = $request->validate([
            'nom' => 'required|string',
            'description' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'region' => 'required|string',
            'objectif' => 'nullable|integer|min:1',
        ]);

        $validated['status'] = 'actif';

        $projet = Projet::create($validated);

        return response()->json([
            'message' => 'Projet créé avec succès.',
            'projet' => $projet,
        ], 201);
    }

    public function show(Request $request, Projet $projet)
    {
        $this->ensureAccess($request, $projet);

        return response()->json([
            'projet' => $projet->load(['parcelles', 'objectives']),
        ]);
    }

    public function update(Request $request, Projet $projet)
    {
        $this->authorize('update', $projet);

        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'description' => 'sometimes|string',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date',
            'region' => 'sometimes|string',
            'objectif' => 'sometimes|nullable|integer|min:1',
        ]);

        $projet->update($validated);

        return response()->json([
            'message' => 'Projet mis à jour avec succès.',
            'projet' => $projet,
        ]);
    }

    public function destroy(Request $request, Projet $projet)
    {
        $this->authorize('delete', $projet);

        \DB::transaction(function () use ($projet) {
            // Supprimer les objectifs liés au projet
            $projet->objectives()->delete();

            // Détacher les utilisateurs (table pivot projet_user)
            $projet->users()->detach();

            // Supprimer les images d'évolution liées au projet
            \App\Models\EvolutionImage::where('projet_id', $projet->id)->delete();

            // Supprimer les plants liés aux parcelles du projet
            $parcelleIds = $projet->parcelles()->pluck('id');
            \App\Models\Plant::whereIn('parcelle_id', $parcelleIds)->delete();

            // Supprimer les parcelles liées au projet
            $projet->parcelles()->delete();

            // Supprimer les coopératives liées au projet
            $projet->cooperatives()->delete();

            // Enfin, supprimer le projet lui-même
            $projet->delete();
        });

        return response()->json([
            'message' => 'Projet et toutes ses données associées ont été supprimés avec succès.',
        ]);
    }

    private function ensureAccess(Request $request, Projet $projet)
    {
        $user = $request->user();
        if ($user->role === 'administrateur')
            return;

        abort_unless($user->projects->contains('id', $projet->id), 403, "Vous n'avez pas accès à ce projet.");
    }
}
