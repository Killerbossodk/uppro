<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;  // ← AJOUT

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;  // ← AJOUT HasApiTokens

    protected $fillable = [
        'name',
        'prenom',
        'email',
        'password',
        'specialite_id',
        'annee_universitaire',
        'role',
        'niveau',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Vos relations (inchangées)
    public function specialite()
    {
        return $this->belongsTo(Specialite::class);
    }
    public function projetsSupervises() { return $this->hasMany(Project::class, 'superviseur_id'); }
    public function groupesMembres() { return $this->belongsToMany(Group::class, 'group_user')->withPivot('est_chef', 'chef_delegue_id', 'joined_at'); }
    public function meetingsOrganises() { return $this->hasMany(Meeting::class, 'organisateur_id'); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function jurys() { return $this->belongsToMany(Jury::class, 'jury_user')->withPivot('role_jury'); }
    // Dans app/Models/User.php

    
}