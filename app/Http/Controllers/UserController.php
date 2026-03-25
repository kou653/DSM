<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $users = User::query()
            ->with(['projects:id,code,name', 'roles:id,name'])
            ->orderBy('nom_complet')
            ->get()
            ->map(fn (User $user) => $this->formatUser($user));

        return response()->json([
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'code_acces' => ['required', 'string', 'max:255', 'unique:users,code_acces'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', Rule::in($this->allowedRoles())],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
        ]);

        $user = User::create([
            'nom_complet' => $validated['nom_complet'],
            'email' => $validated['email'],
            'code_acces' => $validated['code_acces'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles([$validated['role']]);
        $user->projects()->sync($validated['project_ids'] ?? []);
        $user->load(['projects:id,code,name', 'roles:id,name']);

        return response()->json([
            'message' => 'Utilisateur cree avec succes.',
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function show(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->load(['projects:id,code,name', 'roles:id,name']);

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'nom_complet' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'code_acces' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'code_acces')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['sometimes', 'string', Rule::in($this->allowedRoles())],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['integer', 'exists:projects,id'],
        ]);

        $payload = collect($validated)
            ->only(['nom_complet', 'email', 'code_acces'])
            ->all();

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        if ($payload !== []) {
            $user->update($payload);
        }

        if (array_key_exists('role', $validated)) {
            $user->syncRoles([$validated['role']]);
        }

        if (array_key_exists('project_ids', $validated)) {
            $user->projects()->sync($validated['project_ids'] ?? []);
        }

        $user->load(['projects:id,code,name', 'roles:id,name']);

        return response()->json([
            'message' => 'Utilisateur mis a jour avec succes.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->projects()->detach();
        $user->syncRoles([]);
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprime avec succes.',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Cette action est reservee a l administrateur.');
    }

    private function allowedRoles(): array
    {
        return Role::query()
            ->pluck('name')
            ->all();
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'nom_complet' => $user->nom_complet,
            'email' => $user->email,
            'code_acces' => $user->code_acces,
            'statut' => $user->getRoleNames()->first(),
            'date' => $user->created_at,
            'projects' => $user->projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                ];
            })->values(),
        ];
    }
}
