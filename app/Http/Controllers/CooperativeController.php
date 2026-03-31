<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use Illuminate\Http\Request;

class CooperativeController extends Controller
{
    public function index()
    {
        return response()->json([
            'cooperatives' => Cooperative::all(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'nom' => 'required|string',
            'entreprise' => 'required|string',
            'contact' => 'required|string',
            'email' => 'required|email|unique:cooperatives',
            'ville' => 'required|string',
            'village' => 'required|string',
        ]);

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
            'village' => 'sometimes|string',
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

        $cooperative->delete();

        return response()->json([
            'message' => 'Cooperative supprimee avec succes.',
        ]);
    }
}
