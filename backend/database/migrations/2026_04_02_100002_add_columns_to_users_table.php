<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('specialite_id')->nullable()->after('id')->constrained('specialites')->nullOnDelete();
            $table->string('prenom', 100)->after('name');
            $table->string('annee_universitaire', 9)->nullable();
            $table->enum('role', ['professeur', 'etudiant', 'rup_projet', 'rup_specialite'])->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['specialite_id']);
            $table->dropColumn(['specialite_id', 'prenom', 'annee_universitaire', 'role']);
        });
    }
};