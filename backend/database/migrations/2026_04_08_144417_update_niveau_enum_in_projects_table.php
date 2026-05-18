<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
{
    // Convertir les anciens niveaux vers les nouveaux (optionnel)
    DB::table('projects')->update(['niveau' => 'TS1']); // ou autre logique
    DB::statement("ALTER TABLE projects MODIFY COLUMN niveau ENUM('TS1','TS2','TS3','Mélangé') NOT NULL");
}

    public function down()
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN niveau ENUM('L1','L2','L3','M1','M2') NOT NULL");
    }
};