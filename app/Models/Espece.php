<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Espece extends Model
{
    protected $fillable = [
        'nom_commun',
        'nom_scientifique',
    ];

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
