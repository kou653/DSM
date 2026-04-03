<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\Projet;
use Illuminate\Http\Request;

class CooperativeController extends Controller
{
    public function index(Request $request, ?Projet $projet = null)
    {
        $query = Cooperative::query()->with('projet:id,nom');

        if ($projet) {
            $this->ensureProjetAccess($request->user(), $projet);
            $query->where('projet_id', $projet->id);
        } elseif ($request->filled('projet_id')) {
            $requestedProjet = Projet::findOrFail($request->integer('projet_id'));
            $this->ensureProjetAccess($request->user(), $requestedProjet);
            $query->where('projet_id', $requestedProjet->id);
        } elseif ($request->user()->role !== 'administrateur') {
            $query->whereIn('projet_id', $request->user()->projects()->pluck('projets.id'));
        }

        return response()->json([
            'cooperatives' => $query->get(),
        ]);
    }

    public function store(Request $request, ?Projet $projet = null)
    {
        abort_unless($request->user()->role === 'administrateur', 403);

        $targetProjet = $projet;

        if (!$targetProjet) {
            $request->validate([
                'projet_id' => 'required|exists:projets,id',
            ]);

            $targetProjet = Projet::findOrFail($request->integer('projet_id'));
        }

        $validated = $request->validate([
            'nom' => 'required|string',
            'entreprise' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:cooperatives',
            'ville' => 'required|string',
            'village' => 'nullable|string',
        ]);

        $validated['projet_id'] = $targetProjet->id;

        $cooperative = Cooperative::create($validated);

        return response()->json([
            'message' => 'Cooperative creee avec succes.',
            'cooperative' => $cooperative,
        ], 201);
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        abort_unless($request->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'nom' => 'sometimes|string',
            'entreprise' => 'sometimes|string',
            'contact' => 'sometimes|string',
            'email' => 'sometimes|email|unique:cooperatives,email,' . $cooperative->id,
            'ville' => 'sometimes|string',
            'village' => 'sometimes|nullable|string',
        ]);

        $cooperative->update($validated);

        return response()->json([
            'message' => 'Cooperative mise a jour avec succes.',
            'cooperative' => $cooperative,
        ]);
    }

    public function destroy(Request $request, Cooperative $cooperative)
    {
        abort_unless($request->user()->role === 'administrateur', 403);

        if ($cooperative->parcelles()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une cooperative deja utilisee par des parcelles.',
            ], 422);
        }

        $cooperative->delete();

        return response()->json([
            'message' => 'Cooperative supprimee avec succes.',
        ]);
    }
}
