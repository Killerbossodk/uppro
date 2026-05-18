<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->change();
            $table->foreignId('receiver_id')->nullable()->after('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_private')->default(false)->after('receiver_id');
            $table->string('attachment_url', 500)->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable(false)->change();
            $table->dropForeign(['receiver_id']);
            $table->dropColumn(['receiver_id', 'is_private', 'attachment_url']);
        });
    }
};
