<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcelle extends Model
{
    protected $fillable = [
        'CODE',
        'VILLE',
        'COOPERATIVE',
        'LIBELLE',
        'SUPERFICIE',
        'RESPONSABLE',
        'CONTACT',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
