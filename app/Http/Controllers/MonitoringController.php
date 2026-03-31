<?php

namespace App\Http\Controllers;

use App\Models\Projet;
use App\Models\Parcelle;
use App\Models\Plant;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function projectMonitoring(Projet $projet)
    {
        $this->ensureProjetAccess(request()->user(), $projet);

        $plants = Plant::whereHas('parcelle', function ($q) use ($projet) {
            $q->where('projet_id', $projet->id);
        })->get();

        $total = $plants->count();
        $vivant = $plants->where('status', 'vivant')->count();
        $mort = $plants->where('status', 'mort')->count();

        return response()->json([
            'projet_id' => $projet->id,
            'stats_globales' => [
                'total_plants' => $total,
                'vivants' => $vivant,
                'morts' => $mort,
                'taux_survie' => $total > 0 ? round(($vivant / $total) * 100, 2) : 0,
            ]
        ]);
    }

    public function parcelleMonitoring(Parcelle $parcelle)
    {
        $this->ensureParcelleAccess(request()->user(), $parcelle->loadMissing('projet'));

        $plants = $parcelle->plants;
        $total = $plants->count();
        $vivant = $plants->where('status', 'vivant')->count();
        $mort = $plants->where('status', 'mort')->count();

        return response()->json([
            'parcelle_id' => $parcelle->id,
            'stats' => [
                'total_plants' => $total,
                'vivants' => $vivant,
                'morts' => $mort,
                'taux_survie' => $total > 0 ? round(($vivant / $total) * 100, 2) : 0,
            ]
        ]);
    }
}
