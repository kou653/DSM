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

        return response()->json($espece, 201);
    }
}
