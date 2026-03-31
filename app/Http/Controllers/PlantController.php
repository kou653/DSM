<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\Parcelle;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(Request $request, Parcelle $parcelle)
    {
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

        $plant = Plant::create($validated);

        return response()->json([
            'message' => 'Plant enregistré avec succès.',
            'plant' => $plant,
        ], 201);
    }

    public function updateStatus(Request $request, Plant $plant)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);

        $validated = $request->validate([
            'status' => 'required|in:vivant,mort',
        ]);

        $plant->update($validated);

        return response()->json([
            'message' => "État du plant mis à jour.",
            'plant' => $plant,
        ]);
    }
}
