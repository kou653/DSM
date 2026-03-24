<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('projects');
        $assignedProjectIds = $user->projects->pluck('id');
        $isAdmin = $user->hasRole('admin');

        $projects = Project::query()
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) use ($assignedProjectIds, $isAdmin) {
                $canAccess = $isAdmin || $assignedProjectIds->contains($project->id);

                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'partner_name' => $project->partner_name,
                    'status' => $project->status,
                    'description' => $project->description,
                    'created_at' => $project->created_at,
                    'can_access' => $canAccess,
                ];
            })
            ->values();

        return response()->json([
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $user = $request->user()->loadMissing('projects');

        if (!$user->hasRole('admin') && !$user->projects->contains('id', $project->id)) {
            abort(403, 'Vous n avez pas acces a ce projet.');
        }

        $project->load(['parcelles', 'users:id,nom_complet,email']);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'partner_name' => $project->partner_name,
                'status' => $project->status,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'parcelles_count' => $project->parcelles->count(),
                'users' => $project->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'nom_complet' => $user->nom_complet,
                        'email' => $user->email,
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
