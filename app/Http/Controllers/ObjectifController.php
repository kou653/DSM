<?php

namespace App\Http\Controllers;

use App\Models\Objectif;
use App\Models\Projet;
use Illuminate\Http\Request;

class ObjectifController extends Controller
{
    public function index(Projet $projet)
    {
        return response()->json([
            'objectifs' => $projet->objectives()->with('parcelle:id,nom')->get(),
        ]);
    }

    public function update(Request $request, Objectif $objectif)
    {
        abort_unless($request->user()->role === 'administrateur' || $request->user()->role === 'agent terrain', 403);

        $validated = $request->validate([
            'valeur_actuelle' => 'required|integer|min:0',
            'est_valide' => 'sometimes|boolean',
        ]);

        $objectif->update($validated);

        return response()->json([
            'message' => 'Progression mise à jour.',
            'objectif' => $objectif->append('progression_percentage'),
        ]);
    }
}
