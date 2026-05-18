<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('compte_rendus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->unique()->constrained('meetings')->onDelete('cascade');
            $table->foreignId('redacteur_id')->constrained('users')->onDelete('cascade');
            $table->json('contenu');
            $table->tinyInteger('avancement_pct')->unsigned()->default(0);
            $table->text('ia_brouillon')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('compte_rendus');
    }
};