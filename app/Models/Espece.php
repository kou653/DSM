<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Espece extends Model
{
    protected $fillable = [
        'common_name',
        'scientific_name',
    ];

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }

    public function monitorings()
    {
        return $this->hasMany(Monitoring::class);
    }
}
