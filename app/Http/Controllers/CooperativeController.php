<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CooperativeController extends Controller
{
    public function index()
    {
        $cooperatives = Cooperative::query()
            ->withCount(['parcelles', 'plants'])
            ->orderBy('name')
            ->get()
            ->map(fn (Cooperative $cooperative) => $this->formatCooperative($cooperative));

        return response()->json([
            'cooperatives' => $cooperatives,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cooperatives,name'],
            'city' => ['required', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $cooperative = Cooperative::create($validated);
        $cooperative->loadCount(['parcelles', 'plants']);

        return response()->json([
            'message' => 'Cooperative creee avec succes.',
            'cooperative' => $this->formatCooperative($cooperative),
        ], 201);
    }

    public function show(Cooperative $cooperative)
    {
        $cooperative->loadCount(['parcelles', 'plants']);

        return response()->json([
            'cooperative' => $this->formatCooperative($cooperative),
        ]);
    }

    public function update(Request $request, Cooperative $cooperative)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('cooperatives', 'name')->ignore($cooperative->id)],
            'city' => ['sometimes', 'string', 'max:255'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $cooperative->update($validated);
        $cooperative->loadCount(['parcelles', 'plants']);

        return response()->json([
            'message' => 'Cooperative mise a jour avec succes.',
            'cooperative' => $this->formatCooperative($cooperative),
        ]);
    }

    public function destroy(Request $request, Cooperative $cooperative)
    {
        $this->ensureAdmin($request);

        $cooperative->delete();

        return response()->json([
            'message' => 'Cooperative supprimee avec succes.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Cette action est reservee a l administrateur.');
    }

    private function formatCooperative(Cooperative $cooperative): array
    {
        return [
            'id' => $cooperative->id,
            'name' => $cooperative->name,
            'city' => $cooperative->city,
            'responsible_name' => $cooperative->responsible_name,
            'contact_phone' => $cooperative->contact_phone,
            'contact_email' => $cooperative->contact_email,
            'parcelles_count' => $cooperative->parcelles_count,
            'plants_count' => $cooperative->plants_count,
            'created_at' => $cooperative->created_at,
        ];
    }
}
