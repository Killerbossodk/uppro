<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message');
            $table->enum('type', ['rdv', 'rapport', 'note', 'commune', 'alerte']);
            $table->enum('canal', ['app', 'email', 'push_pwa'])->default('app');
            $table->boolean('lu')->default(false);
            $table->boolean('accuse_reception')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};