<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Group extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'project_id', 'nom', 'capacite_max', 'inscription_ouverte'
    ];
    public function project()
{
    return $this->belongsTo(Project::class);
}

public function membres()
{
    return $this->belongsToMany(User::class, 'group_user')->withPivot('est_chef', 'chef_delegue_id', 'joined_at');
}

public function meetings()
{
    return $this->belongsToMany(Meeting::class, 'meeting_group')->withPivot('statut_reponse', 'motif_refus');
}

public function reports()
{
    return $this->hasMany(Report::class);
}

public function phases()
{
    return $this->hasMany(Phase::class);
}
}
