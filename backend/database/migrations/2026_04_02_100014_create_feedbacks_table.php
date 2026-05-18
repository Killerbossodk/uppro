<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('auteur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('pertinence')->unsigned();
            $table->tinyInteger('interet')->unsigned();
            $table->tinyInteger('difficulte')->unsigned();
            $table->string('commentaire', 500)->nullable();
            $table->boolean('anonyme')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedbacks');
    }
};