<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'group_id', 'deposant_id', 'titre', 'fichier_url', 'version',
        'type', 'statut', 'feedback_ia', 'score_plagiat'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function deposant()
    {
        return $this->belongsTo(User::class, 'deposant_id');
    }

    public function comments()
    {
        return $this->hasMany(ReportComment::class);
    }

    public function annotations()
    {
        return $this->hasMany(ReportAnnotation::class);
    }

    protected $casts = [
        'feedback_ia' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}