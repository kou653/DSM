<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cooperative extends Model
{
    protected $fillable = [
        'projet_id',
        'nom',
        'entreprise',
        'contact',
        'email',
        'ville',
        'village',
    ];

    public function parcelles()
    {
        return $this->hasMany(Parcelle::class);
    }

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }
}
