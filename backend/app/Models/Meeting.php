<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'organisateur_id', 'titre', 'ordre_du_jour',
        'date_heure', 'lieu', 'type', 'lien_visio', 'statut_soutenance',
        'started_at', 'ended_at', 'duree_minutes', 'published_to_rup_spe', 'critiques_generales', 'piece_jointe'
    ];

    protected $casts = [
        'date_heure' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'published_to_rup_spe' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function organisateur()
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }

    public function groupes()
    {
        return $this->belongsToMany(Group::class, 'meeting_group')->withPivot('statut_reponse', 'motif_refus');
    }

    public function compteRendu()
    {
        return $this->hasOne(CompteRendu::class);
    }

    public function grade()
    {
        return $this->hasOne(Grade::class);
    }

    public function jury()
    {
        return $this->hasOne(Jury::class);
    }

    /**
     * Surcharge de la sérialisation pour toujours retourner la date au bon format
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        if (isset($array['date_heure']) && $array['date_heure']) {
            $array['date_heure'] = $this->date_heure instanceof \Carbon\Carbon
                ? $this->date_heure->toISOString()
                : \Carbon\Carbon::parse($this->date_heure)->toISOString();
        }
        
        return $array;
    }
}