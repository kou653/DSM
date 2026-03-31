<?php

namespace App\Http\Controllers;

use App\Models\Parcelle;
use App\Models\Projet;
use Illuminate\Http\Request;

class ParcelleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Parcelle::with(['cooperative', 'projet:id,nom']);

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

        $validated = $request->validate([
            'nom' => 'required|string',
            'ville' => 'required|string',
            'cooperative_id' => 'required|exists:cooperatives,id',
            'superficie' => 'required|numeric',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'objectif' => 'nullable|string',
        ]);

        $validated['projet_id'] = $projet->id;
        $parcelle = Parcelle::create($validated);

        return response()->json([
            'message' => 'Parcelle ajoutée avec succès.',
            'parcelle' => $parcelle,
        ], 201);
    }

    public function show(Parcelle $parcelle)
    {
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
            'objectif' => 'nullable|string',
        ]);

        $parcelle->update($validated);

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
