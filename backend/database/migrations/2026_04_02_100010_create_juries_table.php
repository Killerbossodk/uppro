<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('juries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('president_id')->constrained('users')->onDelete('cascade');
            $table->string('salle', 100);
            $table->dateTime('date_heure');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('juries');
    }
};