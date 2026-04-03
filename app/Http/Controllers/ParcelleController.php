<?php

namespace App\Http\Controllers;

use App\Models\Parcelle;
use App\Models\Projet;
use Illuminate\Http\Request;

class ParcelleController extends Controller
{
    public function index(Request $request, ?Projet $projet = null)
    {
        $user = $request->user();
        $query = Parcelle::with(['cooperative', 'projet:id,nom']);

        if ($projet) {
            $this->ensureProjetAccess($user, $projet);
            $query->where('projet_id', $projet->id);
        }

        if ($user->role !== 'administrateur') {
            $query->whereHas('projet.users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($request->filled('projet_id')) {
            $query->where('projet_id', $request->projet_id);
        }

        return response()->json([
            'parcelles' => $query->get(),
        ]);
    }

    public function store(Request $request, Projet $projet)
    {
        $this->authorize('create', Parcelle::class);
        $this->ensureProjetAccess($request->user(), $projet);

        $validated = $request->validate([
            'nom' => 'required|string',
            'ville' => 'required|string',
            'cooperative_id' => 'required|exists:cooperatives,id',
            'superficie' => 'required|numeric',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'objectif' => 'nullable|integer|min:1',
        ]);

        $validated['projet_id'] = $projet->id;

        $objectifParcelle = $validated['objectif'] ?? null;

        if (
        array_key_exists('objectif', $validated) &&
        !is_null($validated['objectif']) &&
        is_null($projet->objectif)
        ) {
            return response()->json([
                'message' => "Impossible de definir un objectif de parcelle sans objectif global sur le projet.",
            ], 422);
        }

        if (!is_null($objectifParcelle) && !is_null($projet->objectif)) {
            $sommeExistante = $projet->parcelles()->sum('objectif');

            if (($sommeExistante + $objectifParcelle) > $projet->objectif) {
                return response()->json([
                    'message' => "La somme des objectifs des parcelles depasse l'objectif global du projet.",
                ], 422);
            }
        }
        $parcelle = Parcelle::create($validated);

        return response()->json([
            'message' => 'Parcelle ajoutée avec succès.',
            'parcelle' => $parcelle,
        ], 201);
    }

    public function show(Request $request, Parcelle $parcelle)
    {
        $this->authorize('view', $parcelle);

        return response()->json([
            'parcelle' => $parcelle->load(['projet', 'cooperative', 'plants.espece']),
        ]);
    }

    public function update(Request $request, Parcelle $parcelle)
    {
        $this->authorize('update', $parcelle);

        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'ville' => 'sometimes|string',
            'cooperative_id' => 'sometimes|exists:cooperatives,id',
            'superficie' => 'sometimes|numeric',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
            'objectif' => 'nullable|integer|min:1',
        ]);

        $parcelle->update($validated);

        $objectifParcelle = $validated['objectif'] ?? $parcelle->objectif;

        $projet = $parcelle->projet;

        if (!is_null($objectifParcelle) && is_null($projet->objectif)) {
            return response()->json([
                'message' => "Impossible de definir un objectif de parcelle sans objectif global sur le projet.",
            ], 422);
        }

        $sommeAutres = $projet->parcelles()
            ->where('id', '!=', $parcelle->id)
            ->sum('objectif');

        if (!is_null($objectifParcelle) && !is_null($projet->objectif)) {
            if (($sommeAutres + $objectifParcelle) > $projet->objectif) {
                return response()->json([
                    'message' => "La somme des objectifs des parcelles depasse l'objectif global du projet.",
                ], 422);
            }
        }


        return response()->json([
            'message' => 'Parcelle modifiée avec succès.',
            'parcelle' => $parcelle,
        ]);
    }

    public function destroy(Request $request, Parcelle $parcelle)
    {
        $this->authorize('delete', $parcelle);

        $parcelle->delete();

        return response()->json([
            'message' => 'Parcelle supprimée avec succès.',
        ]);
    }
}
