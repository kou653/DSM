<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Parcelle;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$parcelles = Parcelle::where('projet_id', 1)->get();
foreach ($parcelles as $p) {
    echo "ID: {$p->id} | Nom: {$p->nom} | Lat: " . (float)$p->lat . " | Lng: " . (float)$p->lng . "\n";
}
