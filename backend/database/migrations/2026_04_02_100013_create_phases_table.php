<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->string('titre', 200);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->tinyInteger('avancement_pct')->unsigned()->default(0);
            $table->boolean('est_jalon')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('phases');
    }
};