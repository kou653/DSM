<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parcelles', function (Blueprint $table) {
            $table->foreignId('espece_id')->nullable()->constrained('especes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parcelles', function (Blueprint $table) {
            $table->dropForeign(['espece_id']);
            $table->dropColumn('espece_id');
        });
    }
};
