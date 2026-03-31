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

        return response()->json($cooperative, 201);
    }
}
