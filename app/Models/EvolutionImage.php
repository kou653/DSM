<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvolutionImage extends Model
{
    protected $fillable = [
        'projet_id',
        'parcelle_id',
        'user_id',
        'url',
        'description',
        'date',
    ];

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
