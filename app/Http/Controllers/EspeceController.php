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
        abort_unless($request->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'nom_commun' => 'required|string',
            'nom_scientifique' => 'required|string',
        ]);

        $espece = Espece::create($validated);

        return response()->json([
            'message' => 'Espece creee avec succes.',
            'espece' => $espece,
        ], 201);
    }

    public function update(Request $request, Espece $espece)
    {
        abort_unless($request->user()->role === 'administrateur', 403);

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
        abort_unless($request->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'especes' => 'required|array|min:1',
            'especes.*.nom_commun' => 'required|string',
            'especes.*.nom_scientifique' => 'required|string',
        ]);

        $createdCount = 0;

        foreach ($validated['especes'] as $item) {
            Espece::create($item);
            $createdCount++;
        }

        return response()->json([
            'message' => "$createdCount especes importees avec succes.",
        ], 201);
    }
}
