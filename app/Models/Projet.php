<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'date_debut',
        'date_fin',
        'region',
        'objectif',
        'status',
    ];

    public function parcelles()
    {
        return $this->hasMany(Parcelle::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class , 'projet_user');
    }

    public function objectives()
    {
        return $this->hasMany(Objectif::class);
    }
}
