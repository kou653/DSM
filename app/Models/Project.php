<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'code',
        'name',
        'partner_name',
        'status',
        'description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    public function parcelles()
    {
        return $this->hasMany(Parcelle::class);
    }

    public function monitorings()
    {
        return $this->hasMany(Monitoring::class);
    }
}
