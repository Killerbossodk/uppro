<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal pour le chat de groupe
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    $group = \App\Models\Group::find($groupId);
    if (!$group) return false;
    
    if ($user->role === 'professeur' && $group->project->superviseur_id === $user->id) return true;
    if ($user->role === 'etudiant' && $group->membres()->where('user_id', $user->id)->exists()) return true;
    if (in_array($user->role, ['rup_projet', 'rup_specialite'])) return true;
    
    return false;
});