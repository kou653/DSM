<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Supprimer la contrainte unique avant de modifier la colonne
            $table->dropUnique(['code_acces']);
            // Rendre la colonne nullable (le champ n'est plus obligatoire)
            $table->string('code_acces')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code_acces')->nullable(false)->change();
            $table->unique('code_acces');
        });
    }
};
