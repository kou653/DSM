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
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('ville');
            $table->foreignId('parcelle_id')->constrained('parcelles')->cascadeOnDelete();
            $table->foreignId('espece_id')->constrained('especes')->restrictOnDelete();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->restrictOnDelete();
            $table->foreignId('etat_sanitaire_id')->nullable()->constrained('etat_sanitaires')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_long', 10, 7)->nullable();
            $table->date('planted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
