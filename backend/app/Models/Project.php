<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'superviseur_id', 'specialite_id', 'titre', 'description',
        'niveau', 'statut', 'motif_refus', 'annee_universitaire', 
        'est_archive', 'is_public', 'likes_count'
    ];

    protected $appends = ['validated_report_url'];

    public function getValidatedReportUrlAttribute()
    {
        // Chercher un rapport avec statut 'valide' parmi les groupes de ce projet
        $group = $this->groupes()->whereHas('reports', function($q) {
            $q->where('statut', 'valide');
        })->first();

        if ($group) {
            $report = $group->reports()->where('statut', 'valide')->first();
            return $report ? $report->fichier_url : null;
        }

        return null;
    }
    public function specialite()
{
    return $this->belongsTo(Specialite::class);
}

public function superviseur()
{
    return $this->belongsTo(User::class, 'superviseur_id');
}

public function groupes()
{
    return $this->hasMany(Group::class);
}

public function likes()
{
    return $this->hasMany(ProjectLike::class);
}

public function isLikedBy(User $user)
{
    return $this->likes()->where('user_id', $user->id)->exists();
}

public function meetings()
{
    return $this->hasMany(Meeting::class);
}

    protected $casts = [
        'est_archive' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function targetedStudents()
    {
        return $this->belongsToMany(User::class, 'project_student', 'project_id', 'user_id')->withTimestamps();
    }
public function archive()
{
    $this->update(['est_archive' => true]);
}

public function restore()
{
    $this->update(['est_archive' => false]);
}

public function forceDelete()
{
    parent::delete();
}

// Scope pour les projets actifs
public function scopeActive($query)
{
    return $query->where('est_archive', false);
}

// Scope pour les projets archivés
public function scopeArchived($query)
{
    return $query->where('est_archive', true);
}
}
