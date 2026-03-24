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
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('parcelle_id')->constrained('parcelles')->cascadeOnDelete();
            $table->foreignId('espece_id')->constrained('especes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('monitored_at');
            $table->unsignedInteger('plants_planted')->default(0);
            $table->unsignedInteger('plants_alive')->default(0);
            $table->unsignedInteger('plants_dead')->default(0);
            $table->text('mortality_cause')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};
