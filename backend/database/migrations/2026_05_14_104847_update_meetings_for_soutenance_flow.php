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
        Schema::table('meetings', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('statut_soutenance');
            $table->dateTime('ended_at')->nullable()->after('started_at');
            $table->integer('duree_minutes')->default(45)->after('ended_at');
            $table->boolean('published_to_rup_spe')->default(false)->after('duree_minutes');
            $table->text('critiques_generales')->nullable()->after('published_to_rup_spe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'ended_at', 'duree_minutes', 'published_to_rup_spe', 'critiques_generales']);
        });
    }
};
