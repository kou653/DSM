<?php

namespace App\Http\Controllers;

use App\Models\EvolutionImage;
use App\Models\Parcelle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvolutionImageController extends Controller
{
    public function index(Parcelle $parcelle)
    {
        return response()->json([
            'evolution' => $parcelle->evolutionImages()->orderByDesc('date')->get(),
        ]);
    }

    public function store(Request $request, Parcelle $parcelle)
    {
        $this->authorize('create', EvolutionImage::class);

        $request->validate([
            'photo' => 'required|image|max:2048',
            'description' => 'required|string',
        ]);

        $path = $request->file('photo')->store('evolutions', 'public');
        $url = asset('storage/' . $path);

        $image = EvolutionImage::create([
            'projet_id' => $parcelle->projet_id,
            'parcelle_id' => $parcelle->id,
            'user_id' => $request->user()->id,
            'url' => $url,
            'description' => $request->description,
            'date' => now(),
        ]);

        return response()->json([
            'message' => 'Image ajoutée avec succès.',
            'image' => $image,
        ], 201);
    }

    public function destroy(Request $request, EvolutionImage $image)
    {
        $this->authorize('delete', $image);

        // Delete from storage if it's a local file
        $fileName = basename($image->url);
        Storage::disk('public')->delete('evolutions/' . $fileName);

        $image->delete();

        return response()->json([
            'message' => 'Image supprimée avec succès.',
        ]);
    }
}
