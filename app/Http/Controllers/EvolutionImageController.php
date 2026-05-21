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
        $this->authorize('view', $parcelle);

        return response()->json([
            'evolution' => $parcelle->evolutionImages()->orderByDesc('date')->get(),
        ]);
    }

    public function store(Request $request, Parcelle $parcelle)
    {
        $this->authorize('create', EvolutionImage::class);
        $this->authorize('view', $parcelle);

        $request->validate([
            'photo'           => 'required|image|max:5120',
            'description'     => 'required|string',
            'date_observation' => 'required|date',
        ]);

        // Stockage direct dans public/uploads/evolutions/ — aucun symlink nécessaire
        $file      = $request->file('photo');
        $filename  = uniqid('evo_') . '_' . time() . '.' . $file->getClientOriginalExtension();
        $uploadDir = public_path('uploads/evolutions');

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        // Stocker le chemin relatif ; l'accesseur du modèle construit l'URL complète
        $relativePath = 'uploads/evolutions/' . $filename;

        $image = EvolutionImage::create([
            'projet_id'   => $parcelle->projet_id,
            'parcelle_id' => $parcelle->id,
            'user_id'     => $request->user()->id,
            'url'         => $relativePath,
            'description' => $request->description,
            'date'        => $request->date_observation,
        ]);

        return response()->json([
            'message' => 'Image ajoutée avec succès.',
            'image'   => $image,
        ], 201);
    }

    public function destroy(Request $request, EvolutionImage $image)
    {
        $this->authorize('delete', $image);

        $rawValue = $image->getRawOriginal('url');
        $filename = basename($rawValue);

        // Nouveau système : public/uploads/evolutions/
        $uploadPath = public_path('uploads/evolutions/' . $filename);
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        } else {
            // Ancien système : storage/app/public/evolutions/
            Storage::disk('public')->delete('evolutions/' . $filename);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image supprimée avec succès.',
        ]);
    }
}
