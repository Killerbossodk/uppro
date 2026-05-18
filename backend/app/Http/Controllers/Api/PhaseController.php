<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Phase;
use Illuminate\Http\Request;

class PhaseController extends Controller
{
    /**
     * Lister les phases d'un groupe
     */
    public function index(Group $group)
    {
        $user = request()->user();
        
        // Vérifier l'accès
        if ($user->role === 'etudiant') {
            $isMember = $group->membres()->where('user_id', $user->id)->exists();
            if (!$isMember) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }
        } elseif ($user->role === 'professeur') {
            if ($group->project->superviseur_id !== $user->id) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }
        }
        
        $phases = $group->phases()->orderBy('date_debut')->get();
        return response()->json($phases);
    }

    /**
     * Créer une nouvelle phase
     */
    public function store(Request $request, Group $group)
    {
        $user = request()->user();
        
        // Vérifier que l'utilisateur est membre du groupe
        $isMember = $group->membres()
            ->where('user_id', $user->id)
            ->exists();
            
        if (!$isMember && $user->role !== 'professeur') {
            return response()->json(['message' => 'Seul un membre du groupe ou le professeur peut ajouter des phases'], 403);
        }
        
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'avancement_pct' => 'nullable|integer|min:0|max:100',
            'est_jalon' => 'boolean',
        ]);
        
        $validated['avancement_pct'] = $validated['avancement_pct'] ?? 0;
        $validated['est_jalon'] = $validated['est_jalon'] ?? false;
        
        $phase = $group->phases()->create($validated);
        
        return response()->json($phase, 201);
    }

    /**
     * Mettre à jour une phase
     */
    public function update(Request $request, Phase $phase)
    {
        $user = request()->user();
        $group = $phase->group;
        
        // Vérifier les droits
        $isMember = $group->membres()
            ->where('user_id', $user->id)
            ->exists();
            
        if (!$isMember && $user->role !== 'professeur') {
            return response()->json(['message' => 'Seul un membre du groupe peut modifier la phase'], 403);
        }
        
        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'avancement_pct' => 'sometimes|integer|min:0|max:100',
            'est_jalon' => 'sometimes|boolean',
        ]);
        
        $phase->update($validated);
        
        return response()->json($phase);
    }

    /**
     * Supprimer une phase
     */
    public function destroy(Phase $phase)
    {
        $user = request()->user();
        $group = $phase->group;
        
        $isChef = $group->membres()
            ->where('user_id', $user->id)
            ->where('est_chef', true)
            ->exists();
            
        if (!$isChef && $user->role !== 'professeur') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $phase->delete();
        
        return response()->json(['message' => 'Phase supprimée']);
    }
}