<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('superviseur_id')->constrained('users');
            $table->foreignId('specialite_id')->nullable()->constrained('specialites')->nullOnDelete();
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->enum('niveau', ['L1', 'L2', 'L3', 'M1', 'M2']);
            $table->enum('statut', ['brouillon', 'soumis', 'valide', 'refuse', 'archive'])->default('brouillon');
            $table->text('motif_refus')->nullable();
            $table->string('annee_universitaire', 9);
            $table->boolean('est_archive')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};