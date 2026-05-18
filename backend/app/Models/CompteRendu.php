<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteRendu extends Model
{
    use HasFactory;

    protected $table = 'compte_rendus';

    protected $fillable = ['meeting_id', 'redacteur_id', 'contenu', 'avancement_pct', 'ia_brouillon'];

    protected $casts = [
        'contenu' => 'array',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function redacteur()
    {
        return $this->belongsTo(User::class, 'redacteur_id');
    }
}