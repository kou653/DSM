<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
        'CODE_PLANT',
        'VILLE',
        'COOPERATIVE',
        'NOM_PARCELLE',
        'gps_lat',
        'gps_long',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
