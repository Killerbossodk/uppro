<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EncadreurFeedback extends Model
{
    use HasFactory;

    protected $table = 'encadreur_feedbacks';

    protected $fillable = [
        'student_id',
        'encadreur_id',
        'note_pedagogique',
        'commentaire',
        'anonyme'
    ];

    protected $casts = [
        'anonyme' => 'boolean',
        'note_pedagogique' => 'integer'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function encadreur()
    {
        return $this->belongsTo(User::class, 'encadreur_id');
    }
}
