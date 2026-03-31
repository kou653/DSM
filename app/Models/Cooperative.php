<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cooperative extends Model
{
    protected $fillable = [
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
}
