<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\Parcelle;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(Request $request, Parcelle $parcelle)
    {
        $this->authorize('view', $parcelle);

        return response()->json([
            'plants' => $parcelle->plants()->with('espece')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);

        $validated = $request->validate([
            'espece_id' => 'required|exists:especes,id',
            'parcelle_id' => 'required|exists:parcelles,id',
            'date_plantation' => 'required|date',
            'status' => 'required|in:vivant,mort',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $parcelle = Parcelle::with('projet')->findOrFail($validated['parcelle_id']);
        $this->authorize('view', $parcelle);

        $plant = Plant::create($validated);
        $parcelle->increment('objectif_atteint');

        return response()->json([
            'message' => 'Plant enregistré avec succès.',
            'plant' => $plant,
        ], 201);
    }

    public function updateStatus(Request $request, Plant $plant)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);
        $plant->loadMissing('parcelle.projet');
        $this->ensurePlantAccess($request->user(), $plant);

        $validated = $request->validate([
            'status' => 'required|in:vivant,mort',
        ]);

        $plant->update($validated);

        return response()->json([
            'message' => "État du plant mis à jour.",
            'plant' => $plant,
        ]);
    }

    public function updateDocumentation(Request $request, Plant $plant)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);
        $plant->loadMissing('parcelle.projet');
        $this->ensurePlantAccess($request->user(), $plant);

        $validated = $request->validate([
            'documentation' => 'nullable|string|max:5000',
        ]);

        $plant->update([
            'documentation' => $validated['documentation'] ?? null,
        ]);

        return response()->json([
            'message' => 'Documentation du plant mise a jour.',
            'plant' => $plant,
        ]);
    }
    public function update(Request $request, Plant $plant)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);
        $plant->loadMissing('parcelle.projet');
        $this->ensurePlantAccess($request->user(), $plant);

        $validated = $request->validate([
            'espece_id' => 'sometimes|exists:especes,id',
            'date_plantation' => 'sometimes|date',
            'status' => 'sometimes|in:vivant,mort',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
        ]);

        $plant->update($validated);

        return response()->json([
            'message' => 'Plant mis à jour avec succès.',
            'plant' => $plant,
        ]);
    }

    public function destroy(Request $request, Plant $plant)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);
        $plant->loadMissing('parcelle.projet');
        $this->ensurePlantAccess($request->user(), $plant);

        $parcelle = $plant->parcelle;
        $plant->delete();
        $parcelle->decrement('objectif_atteint');

        return response()->json([
            'message' => 'Plant supprimé avec succès.',
        ]);
    }
}
