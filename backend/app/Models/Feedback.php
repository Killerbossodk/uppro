<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'auteur_id', 'pertinence', 'interet', 'difficulte', 'commentaire', 'anonyme'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}