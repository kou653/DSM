<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'Libellé',
        'Partenaire',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }

    public function parcelles()
    {
        return $this->hasMany(Parcelle::class);
    }
}
