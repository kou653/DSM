<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cooperatives', 'projet_id')) {
            Schema::table('cooperatives', function (Blueprint $table) {
                $table->foreignId('projet_id')->nullable()->after('id')->constrained();
            });
        }

        $cooperatives = DB::table('cooperatives')->get();

        foreach ($cooperatives as $cooperative) {
            $projectIds = DB::table('parcelles')
                ->where('cooperative_id', $cooperative->id)
                ->distinct()
                ->orderBy('projet_id')
                ->pluck('projet_id')
                ->filter()
                ->values();

            if ($projectIds->isEmpty()) {
                continue;
            }

            $primaryProjectId = $projectIds->shift();

            DB::table('cooperatives')
                ->where('id', $cooperative->id)
                ->update(['projet_id' => $primaryProjectId]);

            foreach ($projectIds as $projectId) {
                $newCooperativeId = DB::table('cooperatives')->insertGetId([
                    'projet_id' => $projectId,
                    'nom' => $cooperative->nom,
                    'entreprise' => $cooperative->entreprise,
                    'contact' => $cooperative->contact,
                    'email' => $this->duplicateScopedEmail($cooperative->email, $projectId),
                    'ville' => $cooperative->ville,
                    'village' => $cooperative->village,
                    'created_at' => $cooperative->created_at,
                    'updated_at' => $cooperative->updated_at,
                ]);

                DB::table('parcelles')
                    ->where('cooperative_id', $cooperative->id)
                    ->where('projet_id', $projectId)
                    ->update(['cooperative_id' => $newCooperativeId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cooperatives', 'projet_id')) {
            Schema::table('cooperatives', function (Blueprint $table) {
                $table->dropConstrainedForeignId('projet_id');
            });
        }
    }

    private function duplicateScopedEmail(string $email, int $projectId): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return "{$email}.p{$projectId}";
        }

        return "{$parts[0]}+p{$projectId}@{$parts[1]}";
    }
};
