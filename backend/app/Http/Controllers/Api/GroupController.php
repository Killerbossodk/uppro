<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    // Liste des groupes (selon rôle)
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        $query = Group::with('project.superviseur', 'membres');

        if ($user->role === 'professeur') {
            $query->whereHas('project', fn($q) => $q->where('superviseur_id', $user->id));
        } elseif ($user->role === 'etudiant') {
            $query->whereHas('membres', fn($q) => $q->where('user_id', $user->id));
        } elseif ($user->role === 'rup_specialite') {
            $query->whereHas('project', fn($q) => $q->where('specialite_id', $user->specialite_id));
        }

        return response()->json($query->get());
    }

    // Créer un groupe pour un projet (professeur)
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        if ($user->role !== 'professeur') {
            return response()->json(['message' => 'Seul un professeur peut créer un groupe'], 403);
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'nom' => 'required|string|max:100',
            'capacite_max' => 'sometimes|integer|min:1|max:10',
        ]);

        $project = Project::find($validated['project_id']);
        if ($project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas ajouter un groupe à ce projet'], 403);
        }

        $group = Group::create([
            'project_id' => $validated['project_id'],
            'nom' => $validated['nom'],
            'capacite_max' => $validated['capacite_max'] ?? 5,
            'inscription_ouverte' => true,
        ]);

        return response()->json($group->load('project'), 201);
    }

    /**
     * Ajouter un étudiant dans un groupe (professeur uniquement)
     */
    public function addMember(Request $request, Group $group)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        $project = $group->project;

        // Seul un professeur encadrant peut ajouter manuellement
        if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $etudiant = User::find($validated['user_id']);
        if ($etudiant->role !== 'etudiant') {
            return response()->json(['message' => 'Seul un étudiant peut être ajouté'], 422);
        }

        // Vérifier la capacité
        if ($group->membres()->count() >= $group->capacite_max) {
            return response()->json(['message' => 'Groupe complet'], 422);
        }

        // Vérifier que l'étudiant n'est pas déjà dans un autre groupe du même projet
        $dejaInscrit = Group::where('project_id', $group->project_id)
            ->whereHas('membres', fn($q) => $q->where('user_id', $etudiant->id))
            ->exists();
        if ($dejaInscrit) {
            return response()->json(['message' => 'Cet étudiant est déjà dans un groupe de ce projet'], 422);
        }

        // Ajouter l'étudiant
        $group->membres()->attach($etudiant->id, ['joined_at' => now()]);

        // Si c'est le premier membre, le désigner automatiquement chef
        if ($group->membres()->count() === 1) {
            $group->membres()->updateExistingPivot($etudiant->id, ['est_chef' => true]);
        }

        return response()->json(['message' => 'Étudiant ajouté avec succès', 'group' => $group->load('membres')]);
    }

    /**
     * Auto-inscription d'un étudiant dans un groupe
     */
    public function selfAdd(Request $request, Group $group)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Non authentifié'], 401);
    }
    if ($user->role !== 'etudiant') {
        return response()->json(['message' => 'Seul un étudiant peut s\'inscrire'], 403);
    }

    // ✅ NOUVEAU : Vérifier que l'étudiant n'est dans AUCUN groupe ACTIF (ignore les projets archivés)
    $existingGroup = Group::whereHas('membres', function($q) use ($user) {
        $q->where('user_id', $user->id);
    })->whereHas('project', function($q) {
        $q->where('est_archive', false);
    })->first();

    if ($existingGroup) {
        return response()->json([
            'message' => 'Vous êtes déjà membre du groupe "' . $existingGroup->nom . '" (projet: ' . $existingGroup->project->titre . '). Vous devez quitter ce groupe avant d\'en rejoindre un autre.'
        ], 422);
    }

    // Vérifier que les inscriptions sont ouvertes
    if (!$group->inscription_ouverte) {
        return response()->json(['message' => 'Les inscriptions sont fermées pour ce groupe'], 422);
    }

    // Vérifier la capacité
    if ($group->membres()->count() >= $group->capacite_max) {
        return response()->json(['message' => 'Groupe complet'], 422);
    }

    // Vérifier la spécialité si le projet a une spécialité définie
    $project = $group->project;
    if ($project->specialite_id !== null && $project->specialite_id !== $user->specialite_id) {
        return response()->json(['message' => 'Ce projet n\'est pas dans votre spécialité'], 403);
    }

    // Ajouter l'étudiant
    $group->membres()->attach($user->id, ['joined_at' => now()]);

    // Si c'est le premier membre, le désigner automatiquement chef
    if ($group->membres()->count() === 1) {
        $group->membres()->updateExistingPivot($user->id, ['est_chef' => true]);
    }

    return response()->json(['message' => 'Inscription réussie', 'group' => $group->load('membres')]);
}

    // Désigner un chef (professeur ou étudiants du groupe)
    public function setChef(Request $request, Group $group)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        $project = $group->project;

        $isMember = $group->membres()->where('user_id', $user->id)->exists();
        if (!($user->role === 'professeur' && $project->superviseur_id === $user->id) && !$isMember) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $nouveauChef = User::find($validated['user_id']);
        if (!$group->membres()->where('user_id', $nouveauChef->id)->exists()) {
            return response()->json(['message' => 'Cet utilisateur n\'est pas membre du groupe'], 422);
        }

        // Retirer le statut chef à tous les membres via la table pivot
        \Illuminate\Support\Facades\DB::table('group_user')->where('group_id', $group->id)->update(['est_chef' => false]);
        // Définir le nouveau chef
        $group->membres()->updateExistingPivot($nouveauChef->id, ['est_chef' => true]);

        return response()->json(['message' => 'Chef désigné avec succès', 'group' => $group->load('membres')]);
    }

    // Retirer un étudiant du groupe (professeur uniquement)
    public function removeMember(Request $request, Group $group)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        $project = $group->project;

        if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Seul le professeur encadrant peut retirer un étudiant'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $membre = User::find($validated['user_id']);
        if (!$group->membres()->where('user_id', $membre->id)->exists()) {
            return response()->json(['message' => 'Cet étudiant n\'est pas dans le groupe'], 422);
        }

        // Vérifier si c'était le chef
        $wasChef = $group->membres()->where('user_id', $membre->id)->first()->pivot->est_chef;
        $group->membres()->detach($membre->id);

        // Si c'était le chef et qu'il reste des membres, désigner un nouveau chef
        if ($wasChef && $group->membres()->count() > 0) {
            $newChef = $group->membres()->first();
            $group->membres()->updateExistingPivot($newChef->id, ['est_chef' => true]);
        }

        return response()->json(['message' => 'Étudiant retiré', 'group' => $group->load('membres')]);
    }

    // Fermer/ouvrir les inscriptions (professeur)
    public function toggleInscriptions(Request $request, Group $group)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        $project = $group->project;

        if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $group->inscription_ouverte = !$group->inscription_ouverte;
        $group->save();

        return response()->json(['message' => 'État des inscriptions modifié', 'inscription_ouverte' => $group->inscription_ouverte]);
    }

    // Afficher un groupe
    public function show(Group $group)
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        
        $project = $group->project;
        
        if ($user->role === 'professeur' && $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        if ($user->role === 'rup_specialite' && $project->specialite_id !== $user->specialite_id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        if ($user->role === 'etudiant' && !$group->membres()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous n\'êtes pas membre de ce groupe'], 403);
        }
        
        return response()->json($group->load('project', 'membres'));
    }
    /**
 * Quitter un groupe (étudiant)
 */
public function leaveGroup(Group $group)
{
    $user = request()->user();
    if ($user->role !== 'etudiant') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    // Vérifier que l'étudiant est membre
    $pivot = $group->membres()->where('user_id', $user->id)->first();
    if (!$pivot) {
        return response()->json(['message' => 'Vous n\'êtes pas membre de ce groupe'], 422);
    }

    // Vérifier si les inscriptions sont ouvertes
    if (!$group->inscription_ouverte) {
        return response()->json(['message' => 'Les inscriptions sont fermées, vous ne pouvez plus quitter le groupe'], 422);
    }

    // Empêcher le chef de quitter s'il est le seul membre ?
    if ($pivot->pivot->est_chef && $group->membres()->count() > 1) {
        return response()->json(['message' => 'Vous êtes chef. Désignez un nouveau chef avant de quitter.'], 422);
    }

    $group->membres()->detach($user->id);

    // Si c'était le chef et qu'il reste des membres, désigner un nouveau chef automatiquement
    if ($pivot->pivot->est_chef && $group->membres()->count() > 0) {
        $newChef = $group->membres()->first();
        $group->membres()->updateExistingPivot($newChef->id, ['est_chef' => true]);
    }

    return response()->json(['message' => 'Vous avez quitté le groupe. Vous pouvez maintenant rejoindre un autre groupe.']);
}
/**
 * Dissoudre un groupe (RUP Projet uniquement)
 */
/**
 * Dissoudre un groupe (RUP Projet uniquement)
 */
public function dissoudre(Request $request, Group $group)
{
    $user = $request->user();
    
    if ($user->role !== 'rup_projet') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $project = $group->project;

    // Nettoyer les données du groupe
    \App\Models\Message::where('group_id', $group->id)->delete();
    $group->membres()->detach();
    $group->phases()->delete();
    $group->reports()->delete();

    // Notifier le professeur
    if ($project && $project->superviseur_id) {
        \App\Models\Notification::create([
            'user_id' => $project->superviseur_id,
            'message' => "Le groupe '{$group->nom}' du projet '{$project->titre}' a été dissous.",
            'type' => 'info',
            'canal' => 'app',
            'lu' => false,
        ]);
    }

    $group->delete();

    return response()->json(['message' => 'Groupe dissous avec succès']);
}
}