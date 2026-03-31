<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Objectif extends Model
{
    protected $fillable = [
        'projet_id',
        'parcelle_id',
        'titre',
        'valeur_cible',
        'valeur_actuelle',
        'unite',
        'est_valide',
    ];

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    /**
     * Get the percentage of progression.
     */
    public function getProgressionPercentageAttribute(): float
    {
        if ($this->valeur_cible <= 0) {
            return 0;
        }

        return round(($this->valeur_actuelle / $this->valeur_cible) * 100, 2);
    }
}
