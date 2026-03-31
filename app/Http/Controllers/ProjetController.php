<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use Illuminate\Http\Request;

class ProjetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Projet::query();

        // Filtering by role
        if ($user->role !== 'administrateur') {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
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
            'date_fin' => 'required|date',
            'region' => 'required|string',
            'status' => 'required|in:actif,termine,en_pause',
        ]);

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
            'status' => 'sometimes|in:actif,termine,en_pause',
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

        $projet->delete();

        return response()->json([
            'message' => 'Projet supprimé avec succès.',
        ]);
    }

    private function ensureAccess(Request $request, Projet $projet)
    {
        $user = $request->user();
        if ($user->role === 'administrateur') return;

        abort_unless($user->projects->contains('id', $projet->id), 403, "Vous n'avez pas accès à ce projet.");
    }
}
