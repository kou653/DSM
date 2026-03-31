<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('projects');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion reussie',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUserPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Deconnexion reussie',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('projects');

        return response()->json([
            'user' => $this->formatUserPayload($user),
        ]);
    }

    private function formatUserPayload($user): array
    {
        return [
            'id' => $user->id,
            'nom_complet' => $user->nom_complet,
            'email' => $user->email,
            'code_acces' => $user->code_acces,
            'role' => $user->role, // Using the new role column
            'projects' => $user->projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'nom' => $project->nom,
                    'status' => $project->status,
                ];
            })->values(),
        ];
    }
}
