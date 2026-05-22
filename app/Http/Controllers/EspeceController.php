<?php

namespace App\Http\Controllers;

use App\Models\Espece;
use Illuminate\Http\Request;

class EspeceController extends Controller
{
    public function index()
    {
        return response()->json([
            'especes' => Espece::all(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['administrateur', 'agent terrain']), 403);

        $validated = $request->validate([
            'nom_commun' => 'required|string',
            'nom_scientifique' => 'required|string',
        ]);

        $exists = Espece::whereRaw('LOWER(nom_commun) = ?', [strtolower($validated['nom_commun'])])->exists();

        if ($exists) {
            return response()->json([
                'message' => "L'espèce \"{$validated['nom_commun']}\" existe déjà.",
            ], 409);
        }

        $espece = Espece::create($validated);

        return response()->json([
            'message' => 'Espece creee avec succes.',
            'espece' => $espece,
        ], 201);
    }

    public function update(Request $request, Espece $espece)
    {
        abort_unless(in_array($request->user()->role, ['administrateur', 'agent terrain']), 403);

        $validated = $request->validate([
            'nom_commun' => 'sometimes|string',
            'nom_scientifique' => 'sometimes|string',
        ]);

        $espece->update($validated);

        return response()->json([
            'message' => 'Espece mise a jour avec succes.',
            'espece' => $espece,
        ]);
    }

    public function destroy(Request $request, Espece $espece)
    {
        abort_unless($request->user()->role === 'administrateur', 403);

        $espece->delete();

        return response()->json([
            'message' => 'Espece supprimee avec succes.',
        ]);
    }

    public function bulkStore(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['administrateur', 'agent terrain']), 403);

        $validated = $request->validate([
            'especes' => 'required|array|min:1',
            'especes.*.nom_commun' => 'required|string',
            'especes.*.nom_scientifique' => 'required|string',
        ]);

        $duplicates = [];
        $toCreate = [];

        foreach ($validated['especes'] as $item) {
            $exists = Espece::whereRaw('LOWER(nom_commun) = ?', [strtolower($item['nom_commun'])])->exists();
            if ($exists) {
                $duplicates[] = $item['nom_commun'];
            } else {
                $toCreate[] = $item;
            }
        }

        // Toutes les espèces existent déjà → rien à enregistrer
        if (!empty($duplicates) && empty($toCreate)) {
            return response()->json([
                'message' => "Aucune espèce importée. Ces espèces existent déjà.",
            ], 409);
        }

        // Enregistrer uniquement les nouvelles
        foreach ($toCreate as $item) {
            Espece::create($item);
        }

        $createdCount = count($toCreate);
        $response = [
            'message' => "$createdCount espèce(s) importée(s) avec succès.",
        ];

        // Signaler les doublons ignorés
        if (!empty($duplicates)) {
            $liste = implode(', ', $duplicates);
            $response['duplicates_message'] = "Ces espèces existaient déjà et n'ont pas été importées : $liste.";
        }

        return response()->json($response, 201);
    }
}
