<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
        'code',
        'ville',
        'parcelle_id',
        'espece_id',
        'cooperative_id',
        'etat_sanitaire_id',
        'user_id',
        'gps_lat',
        'gps_long',
        'planted_at',
        'notes',
    ];

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }

    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function etatSanitaire()
    {
        return $this->belongsTo(EtatSanitaire::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
