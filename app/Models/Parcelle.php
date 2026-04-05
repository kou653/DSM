<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcelle extends Model
{
    protected $fillable = [
        'nom',
        'ville',
        'cooperative_id',
        'projet_id',
        'superficie',
        'lat',
        'lng',
        'objectif',
        'objectif_atteint',
        'espece_id',
    ];

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }

    public function evolutionImages()
    {
        return $this->hasMany(EvolutionImage::class);
    }
}
