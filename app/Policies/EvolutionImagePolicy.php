<?php

namespace App\Policies;

use App\Models\EvolutionImage;
use App\Models\User;

class EvolutionImagePolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role !== 'commanditaire';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EvolutionImage $evolutionImage): bool
    {
        return $user->role === 'administrateur';
    }
}
