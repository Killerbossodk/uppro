<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('est_chef')->default(false);
            $table->foreignId('chef_delegue_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('joined_at')->useCurrent();
            $table->primary(['group_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_user');
    }
};