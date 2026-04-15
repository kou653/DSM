<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
        'espece_id',
        'parcelle_id',
        'date_plantation',
        'status',
        'lat',
        'lng',
        'documentation',
    ];

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }
}
