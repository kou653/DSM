<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'administrateur', 403);

        return response()->json([
            'users' => User::with('projects:id,nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'nom_complet' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:administrateur,agent terrain,commanditaire',
            'code_acces' => 'nullable|string',
            'projects' => 'sometimes|array',
            'projects.*' => 'exists:projets,id',
        ]);

        $user = User::create([
            ...collect($validated)->except('projects')->all(),
            'password' => Hash::make($validated['password']),
        ]);

        if (isset($validated['projects'])) {
            $user->projects()->sync($validated['projects']);
        }

        return response()->json([
            'message' => 'Utilisateur cree avec succes.',
            'user' => $user->load('projects:id,nom'),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'nom_complet' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'code_acces' => 'nullable|string',
            'role' => 'sometimes|in:administrateur,agent terrain,commanditaire',
            'projects' => 'sometimes|array',
            'projects.*' => 'exists:projets,id',
        ]);

        $user->update(collect($validated)->except('projects')->all());

        if (isset($validated['projects'])) {
            $user->projects()->sync($validated['projects']);
        }

        return response()->json([
            'message' => 'Utilisateur mis a jour.',
            'user' => $user->load('projects:id,nom'),
        ]);
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->role === 'administrateur', 403);

        $user->projects()->detach();
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprime.',
        ]);
    }
}
