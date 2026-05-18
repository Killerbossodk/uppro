<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $table = 'grades';

    protected $fillable = [
        'meeting_id', 'group_id', 'evaluateur_id', 'note', 'note_ia_suggeree',
        'commentaire', 'valide_par_rup', 'publiee'
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function evaluateur()
    {
        return $this->belongsTo(User::class, 'evaluateur_id');
    }
}