<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN niveau ENUM('TS1','TS2','TS3','L1','L2','L3','M1','M2','Mélangé') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN niveau ENUM('L1','L2','L3','M1','M2') NOT NULL");
    }
};