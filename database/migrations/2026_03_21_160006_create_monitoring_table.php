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
        Schema::create('monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('Espece')->constrained('especes')->onDelete('cascade');
            $table->integer('Nb plantés');
            $table->integer('Nb vivants');
            $table->integer('Nb morts');
            $table->timestamps();
            // $table->foreignId('Réalisé par')->constrained('users')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring');
    }
};
