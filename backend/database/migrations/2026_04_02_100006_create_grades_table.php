<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings');
            $table->foreignId('evaluateur_id')->constrained('users');
            $table->decimal('note', 4, 2);
            $table->decimal('note_ia_suggeree', 4, 2)->nullable();
            $table->text('commentaire')->nullable();
            $table->boolean('valide_par_rup')->default(false);
            $table->boolean('publiee')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grades');
    }
};