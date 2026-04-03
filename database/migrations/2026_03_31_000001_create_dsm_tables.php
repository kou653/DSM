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
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('region');
            $table->unsignedInteger('objectif')->nullable();
            $table->enum('status', ['actif', 'termine', 'en_pause']);
            $table->timestamps();
        });

        Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained();
            $table->string('nom');
            $table->string('entreprise');
            $table->string('contact');
            $table->string('email')->unique();
            $table->string('ville');
            $table->string('village')->nullable();
            $table->timestamps();
        });

        Schema::create('parcelles', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ville');
            $table->foreignId('cooperative_id')->constrained();
            $table->foreignId('projet_id')->constrained();
            $table->decimal('superficie', 8, 2);
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->unsignedInteger('objectif')->nullable();
            $table->timestamps();
        });

        Schema::create('especes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_commun');
            $table->string('nom_scientifique');
            $table->timestamps();
        });

        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espece_id')->constrained();
            $table->foreignId('parcelle_id')->constrained();
            $table->date('date_plantation');
            $table->enum('status', ['vivant', 'mort']);
            $table->decimal('lat', 10, 8);
            $table->decimal('lng', 11, 8);
            $table->timestamps();
        });

        Schema::create('evolution_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained();
            $table->foreignId('parcelle_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('url');
            $table->string('description');
            $table->timestamp('date');
            $table->timestamps();
        });

        Schema::create('projet_user', function (Blueprint $table) {
            $table->foreignId('projet_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->primary(['projet_id', 'user_id']);
        });

        Schema::create('objectifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_id')->constrained();
            $table->foreignId('parcelle_id')->nullable()->constrained();
            $table->string('titre');
            $table->integer('valeur_cible');
            $table->integer('valeur_actuelle');
            $table->string('unite')->default('plants');
            $table->boolean('est_valide')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objectifs');
        Schema::dropIfExists('projet_user');
        Schema::dropIfExists('evolution_images');
        Schema::dropIfExists('plants');
        Schema::dropIfExists('especes');
        Schema::dropIfExists('parcelles');
        Schema::dropIfExists('cooperatives');
        Schema::dropIfExists('projets');
    }
};
