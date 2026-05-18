<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // MySQL : modifier la colonne enum
        DB::statement("ALTER TABLE projects MODIFY COLUMN niveau ENUM('TS1','TS2','TS3','Mélangé') NOT NULL");
    }

    public function down()
    {
        // Retour à l'ancien (au cas où)
        DB::statement("ALTER TABLE projects MODIFY COLUMN niveau ENUM('L1','L2','L3','M1','M2') NOT NULL");
    }
};