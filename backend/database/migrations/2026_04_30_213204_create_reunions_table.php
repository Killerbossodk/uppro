<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reunions', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description');
            $table->dateTime('date_heure');
            $table->string('lieu')->nullable();
            $table->string('lien_visio')->nullable();
            $table->foreignId('organisateur_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('reunion_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reunion_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('statut', ['invite', 'accepte', 'refuse'])->default('invite');
            $table->timestamps();
            
            $table->unique(['reunion_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reunion_participant');
        Schema::dropIfExists('reunions');
    }
};