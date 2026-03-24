<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cooperative extends Model
{
    protected $fillable = [
        'name',
        'city',
        'responsible_name',
        'contact_phone',
        'contact_email',
    ];

    public function parcelles()
    {
        return $this->hasMany(Parcelle::class);
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
