<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'titre', 'date_debut', 'date_fin', 'avancement_pct', 'est_jalon'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}