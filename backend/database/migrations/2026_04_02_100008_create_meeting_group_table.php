<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meeting_group', function (Blueprint $table) {
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->enum('statut_reponse', ['en_attente', 'accepte', 'refuse'])->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->primary(['meeting_id', 'group_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_group');
    }
};