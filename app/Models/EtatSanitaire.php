<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtatSanitaire extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
