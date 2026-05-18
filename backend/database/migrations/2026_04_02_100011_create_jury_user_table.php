<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jury_user', function (Blueprint $table) {
            $table->foreignId('jury_id')->constrained('juries')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role_jury', ['president', 'examinateur']);
            $table->primary(['jury_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('jury_user');
    }
};