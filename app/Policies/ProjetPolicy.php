<?php

namespace App\Policies;

use App\Models\Projet;
use App\Models\User;

class ProjetPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Projet $projet): bool
    {
        if ($user->role === 'administrateur') {
            return true;
        }

        return $user->projects->contains('id', $projet->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Projet $projet): bool
    {
        return $user->role === 'administrateur';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Projet $projet): bool
    {
        return $user->role === 'administrateur';
    }
}
