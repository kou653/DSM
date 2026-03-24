<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{
    protected $fillable = [
        'project_id',
        'parcelle_id',
        'espece_id',
        'user_id',
        'monitored_at',
        'plants_planted',
        'plants_alive',
        'plants_dead',
        'mortality_cause',
        'observation',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
