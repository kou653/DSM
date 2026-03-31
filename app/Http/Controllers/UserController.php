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
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'user' => $user,
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->role === 'administrateur', 403);

        $validated = $request->validate([
            'role' => 'required|in:administrateur,agent terrain,commanditaire',
            'projects' => 'array',
            'projects.*' => 'exists:projets,id',
        ]);

        $user->update(['role' => $validated['role']]);

        if (isset($validated['projects'])) {
            $user->projects()->sync($validated['projects']);
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour.',
            'user' => $user->load('projects:id,nom'),
        ]);
    }
}
