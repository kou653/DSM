<?php

namespace App\Policies;

use App\Models\Parcelle;
use App\Models\User;

class ParcellePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Parcelle $parcelle): bool
    {
        if ($user->role === 'administrateur') {
            return true;
        }

        return $user->projects->contains('id', $parcelle->projet_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role !== 'commanditaire';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Parcelle $parcelle): bool
    {
        return $user->role !== 'commanditaire';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Parcelle $parcelle): bool
    {
        return $user->role === 'administrateur';
    }
}
