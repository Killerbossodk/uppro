<?php
// app/Models/Reunion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reunion extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'date_heure',
        'lieu',
        'lien_visio',
        'organisateur_id'
    ];

    protected $casts = [
        'date_heure' => 'datetime',
    ];

    public function organisateur()
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'reunion_participant')
                    ->withPivot('statut')
                    ->withTimestamps();
    }
}