<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtatSanitaire extends Model
{
    protected $table = 'etat_sanitaire';

    protected $fillable = [
        'etat',
    ];
}
