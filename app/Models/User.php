<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nom_complet',
        'email',
        'code_acces',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->belongsToMany(Projet::class, 'projet_user');
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
