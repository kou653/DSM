<?php

namespace App\Http\Controllers;

use App\Models\Parcelle;
use App\Models\Plant;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function isAdmin(?User $user): bool
    {
        return $user?->role === 'administrateur';
    }

    protected function ensureProjetAccess(User $user, Projet $projet): void
    {
        if ($this->isAdmin($user)) {
            return;
        }

        abort_unless(
            $user->projects()->whereKey($projet->id)->exists(),
            403,
            'Vous n\'avez pas acces a ce projet.'
        );
    }

    protected function ensureParcelleAccess(User $user, Parcelle $parcelle): void
    {
        $this->ensureProjetAccess($user, $parcelle->projet);
    }

    protected function ensurePlantAccess(User $user, Plant $plant): void
    {
        $this->ensureParcelleAccess($user, $plant->parcelle);
    }
}
