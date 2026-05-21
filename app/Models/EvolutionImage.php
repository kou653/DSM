<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EvolutionImage extends Model
{
    protected $fillable = [
        'projet_id',
        'parcelle_id',
        'user_id',
        'url',
        'description',
        'date',
    ];

    /**
     * Retourne toujours une URL absolue correcte.
     *
     * Cas supportés :
     *  - URL complète déjà stockée (http://… ou https://…)  → retournée telle quelle
     *  - Chemin relatif dans public/    (uploads/evolutions/x.jpg) → APP_URL + "/" + chemin
     *  - Chemin relatif dans storage    (evolutions/x.jpg)         → Storage::disk('public')->url()
     */
    public function getUrlAttribute($value): string
    {
        if (!$value) return '';

        // Déjà une URL complète (nouveau ou ancien format avec URL stockée)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Chemin dans public/uploads/ (nouveau système — pas de symlink)
        if (str_starts_with($value, 'uploads/')) {
            return rtrim(config('app.url'), '/') . '/' . $value;
        }

        // Chemin dans storage/app/public/ (ancien système — symlink)
        return Storage::disk('public')->url($value);
    }

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }

    public function parcelle()
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
