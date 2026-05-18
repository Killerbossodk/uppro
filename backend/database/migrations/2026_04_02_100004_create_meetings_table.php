<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('organisateur_id')->constrained('users');
            $table->string('titre', 200);
            $table->text('ordre_du_jour')->nullable();
            $table->dateTime('date_heure');
            $table->string('lieu', 200)->nullable();
            $table->enum('type', ['rdv_pilotage', 'soutenance', 'depot_rapport']);
            $table->string('lien_visio', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meetings');
    }
};