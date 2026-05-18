<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jury extends Model
{
    use HasFactory;

    protected $fillable = ['meeting_id', 'president_id', 'salle', 'date_heure'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function president()
    {
        return $this->belongsTo(User::class, 'president_id');
    }

    public function membres()
    {
        return $this->belongsToMany(User::class, 'jury_user')->withPivot('role_jury');
    }
}