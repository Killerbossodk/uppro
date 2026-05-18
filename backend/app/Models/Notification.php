<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'message', 'type', 'canal', 'lu', 'accuse_reception', 'data'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}