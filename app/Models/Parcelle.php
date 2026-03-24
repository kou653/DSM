<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcelle extends Model
{
    protected $fillable = [
        'code',
        'project_id',
        'cooperative_id',
        'name',
        'city',
        'surface_area',
        'surface_unit',
        'responsible_name',
        'contact_phone',
        'gps_lat',
        'gps_long',
        'notes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }

    public function monitorings()
    {
        return $this->hasMany(Monitoring::class);
    }
}
