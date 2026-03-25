<?php

namespace App\Http\Controllers;

use App\Models\Espece;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EspeceController extends Controller
{
    public function index()
    {
        $especes = Espece::query()
            ->orderBy('common_name')
            ->get()
            ->map(fn (Espece $espece) => $this->formatEspece($espece));

        return response()->json([
            'especes' => $especes,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'common_name' => ['required', 'string', 'max:255'],
            'scientific_name' => ['required', 'string', 'max:255', 'unique:especes,scientific_name'],
        ]);

        $espece = Espece::create($validated);

        return response()->json([
            'message' => 'Espece creee avec succes.',
            'espece' => $this->formatEspece($espece),
        ], 201);
    }

    public function show(Espece $espece)
    {
        return response()->json([
            'espece' => $this->formatEspece($espece),
        ]);
    }

    public function update(Request $request, Espece $espece)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'common_name' => ['sometimes', 'string', 'max:255'],
            'scientific_name' => ['sometimes', 'string', 'max:255', Rule::unique('especes', 'scientific_name')->ignore($espece->id)],
        ]);

        $espece->update($validated);

        return response()->json([
            'message' => 'Espece mise a jour avec succes.',
            'espece' => $this->formatEspece($espece),
        ]);
    }

    public function destroy(Request $request, Espece $espece)
    {
        $this->ensureAdmin($request);

        $espece->delete();

        return response()->json([
            'message' => 'Espece supprimee avec succes.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Cette action est reservee a l administrateur.');
    }

    private function formatEspece(Espece $espece): array
    {
        return [
            'id' => $espece->id,
            'common_name' => $espece->common_name,
            'scientific_name' => $espece->scientific_name,
            'created_at' => $espece->created_at,
        ];
    }
}
