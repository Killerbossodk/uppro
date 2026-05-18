<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('deposant_id')->constrained('users');
            $table->string('titre', 200);
            $table->string('fichier_url', 500);
            $table->smallInteger('version')->unsigned()->default(1);
            $table->enum('type', ['intermediaire', 'final', 'corrige']);
            $table->enum('statut', ['soumis', 'en_analyse', 'valide', 'rejete'])->default('soumis');
            $table->text('feedback_ia')->nullable();
            $table->decimal('score_plagiat', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
};