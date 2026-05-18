<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    // Liste des projets (selon le rôle)
    public function index(Request $request)
    {
        $user = $request->user();
        
        // ✅ Ne montrer que les projets NON archivés
        $query = Project::where('est_archive', false)
            ->with('superviseur', 'specialite', 'groupes', 'groupes.membres', 'targetedStudents');
    
        if ($user->role === 'professeur') {
            $query->where('superviseur_id', $user->id);
        } 
        elseif ($user->role === 'etudiant') {
            $query->where('statut', 'valide')
                  ->where(function ($q) use ($user) {
                      $q->where('niveau', $user->niveau)
                        ->orWhere('niveau', 'Mélangé');
                  })
                  ->where(function ($q) use ($user) {
                      $q->where('specialite_id', $user->specialite_id)
                        ->orWhereNull('specialite_id');
                  });
        } 
        elseif ($user->role === 'rup_specialite') {
            $query->where(function ($q) use ($user) {
                $q->where('specialite_id', $user->specialite_id)
                  ->orWhereNull('specialite_id');
            });
        }
        // rup_projet voit tous les actifs
    
        return response()->json($query->orderBy('created_at', 'desc')->get());
    }
    
    // ✅ Tous les projets archivés (tout le monde)
    public function archived()
    {
        $projects = Project::where('est_archive', true)
            ->with('superviseur', 'specialite', 'groupes')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($projects);
    }   // Créer un projet (professeur)
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'professeur') {
            return response()->json(['message' => 'Seul un professeur peut créer un projet'], 403);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'nullable|string',
            'niveau' => 'required|in:TS1,TS2,TS3,Mélangé',
            'specialite_id' => 'nullable|exists:specialites,id',
            'annee_universitaire' => 'required|string|max:9',
            'is_public' => 'sometimes|boolean',
            'targeted_student_ids' => 'nullable|array',
            'targeted_student_ids.*' => 'exists:users,id',
        ]);

        // Si niveau est "Mélangé", on force specialite_id à null
        if ($validated['niveau'] === 'Mélangé') {
            $validated['specialite_id'] = null;
        }

        // Vérifier la limite de 5 projets par niveau pour ce professeur
        $count = Project::where('superviseur_id', $user->id)
                        ->where('niveau', $validated['niveau'])
                        ->whereIn('statut', ['brouillon', 'soumis', 'valide'])
                        ->where('est_archive', false)
                        ->count();
        if ($count >= 5) {
            return response()->json(['message' => 'Vous avez atteint la limite de 5 projets pour ce niveau'], 422);
        }

        $project = Project::create([
            'superviseur_id' => $user->id,
            'specialite_id' => $validated['specialite_id'],
            'titre' => $validated['titre'],
            'description' => $validated['description'],
            'niveau' => $validated['niveau'],
            'annee_universitaire' => $validated['annee_universitaire'],
            'is_public' => $request->input('is_public', true),
            'statut' => 'brouillon',
            'est_archive' => false,
        ]);

        if (!empty($validated['targeted_student_ids'])) {
            $project->targetedStudents()->sync($validated['targeted_student_ids']);

            // 1. Detach targeted students from any other active groups of non-archived projects
            foreach ($validated['targeted_student_ids'] as $studentId) {
                \Illuminate\Support\Facades\DB::table('group_user')
                    ->where('user_id', $studentId)
                    ->whereIn('group_id', function ($query) {
                        $query->select('id')
                              ->from('groups')
                              ->whereIn('project_id', function ($q) {
                                  $q->select('id')->from('projects')->where('est_archive', false);
                              });
                    })
                    ->delete();
            }

            // 2. Create the Group for this project
            $group = \App\Models\Group::create([
                'project_id' => $project->id,
                'nom' => 'Groupe - ' . substr($project->titre, 0, 30),
                'capacite_max' => max(3, count($validated['targeted_student_ids'])),
                'inscription_ouverte' => false,
            ]);

            // 3. Attach students to the new group
            foreach ($validated['targeted_student_ids'] as $index => $studentId) {
                $group->membres()->attach($studentId, [
                    'joined_at' => now(),
                    'est_chef' => ($index === 0) ? true : false,
                ]);
            }
        }

        return response()->json($project, 201);
    }

    // Modifier un projet (professeur, tant que brouillon)
    public function update(Request $request, Project $project)
    {
        $user = $request->user();
        if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        if ($project->statut !== 'brouillon') {
            return response()->json(['message' => 'Ce projet ne peut plus être modifié'], 422);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'niveau' => 'sometimes|in:TS1,TS2,TS3,Mélangé',
            'specialite_id' => 'nullable|exists:specialites,id',
            'annee_universitaire' => 'sometimes|string|max:9',
            'is_public' => 'sometimes|boolean',
            'targeted_student_ids' => 'nullable|array',
            'targeted_student_ids.*' => 'exists:users,id',
        ]);

        // Si niveau devient "Mélangé", on efface la spécialité
        if (isset($validated['niveau']) && $validated['niveau'] === 'Mélangé') {
            $validated['specialite_id'] = null;
        }

        $project->update($validated);

        if (isset($validated['targeted_student_ids'])) {
            $project->targetedStudents()->sync($validated['targeted_student_ids']);

            if (!empty($validated['targeted_student_ids'])) {
                // Find or create group
                $group = \App\Models\Group::firstOrCreate(
                    ['project_id' => $project->id],
                    [
                        'nom' => 'Groupe - ' . substr($project->titre, 0, 30),
                        'capacite_max' => max(3, count($validated['targeted_student_ids'])),
                        'inscription_ouverte' => false,
                    ]
                );

                // Detach these students from other active groups first
                foreach ($validated['targeted_student_ids'] as $studentId) {
                    \Illuminate\Support\Facades\DB::table('group_user')
                        ->where('user_id', $studentId)
                        ->where('group_id', '!=', $group->id)
                        ->whereIn('group_id', function ($query) {
                            $query->select('id')
                                  ->from('groups')
                                  ->whereIn('project_id', function ($q) {
                                      $q->select('id')->from('projects')->where('est_archive', false);
                                  });
                        })
                        ->delete();
                }

                // Sync group members
                $group->membres()->detach();
                foreach ($validated['targeted_student_ids'] as $index => $studentId) {
                    $group->membres()->attach($studentId, [
                        'joined_at' => now(),
                        'est_chef' => ($index === 0) ? true : false,
                    ]);
                }
            } else {
                // Detach all group members if targeted list is empty
                $group = \App\Models\Group::where('project_id', $project->id)->first();
                if ($group) {
                    $group->membres()->detach();
                }
            }
        }

        return response()->json($project);
    }

    // Soumettre le projet (professeur)
    public function soumettre(Project $project)
    {
        $user = request()->user();
        if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        if ($project->statut !== 'brouillon') {
            return response()->json(['message' => 'Seul un projet en brouillon peut être soumis'], 422);
        }

        $project->update(['statut' => 'soumis']);

        // Notification pour le RUP Projet
        $rups = \App\Models\User::where('role', 'rup_projet')->get();
        foreach ($rups as $rup) {
            \App\Models\Notification::create([
                'user_id' => $rup->id,
                'message' => "Le professeur {$user->prenom} {$user->name} a soumis un nouveau projet : {$project->titre}",
                'type' => 'info',
                'canal' => 'app',
                'lu' => false,
            ]);
        }

        return response()->json(['message' => 'Projet soumis avec succès', 'project' => $project]);
    }

    // Valider un projet (RUP Projet)
    public function valider(Project $project)
    {
        $user = request()->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Seul le RUP Projet peut valider les projets'], 403);
        }
        if ($project->statut !== 'soumis') {
            return response()->json(['message' => 'Seul un projet soumis peut être validé'], 422);
        }

        $project->update(['statut' => 'valide']);

        // Notification pour le professeur
        \App\Models\Notification::create([
            'user_id' => $project->superviseur_id,
            'message' => "Votre projet \"{$project->titre}\" a été validé par le RUP Projet.",
            'type' => 'success',
            'canal' => 'app',
            'lu' => false,
        ]);

        // Notification pour les étudiants éligibles
        $studentsQuery = \App\Models\User::where('role', 'etudiant');
        
        // Filtre par niveau
        if ($project->niveau !== 'Mélangé') {
            $studentsQuery->where('niveau', $project->niveau);
        }

        // Filtre par spécialité
        if ($project->specialite_id !== null) {
            $studentsQuery->where('specialite_id', $project->specialite_id);
        }

        $students = $studentsQuery->get();
        foreach ($students as $student) {
            \App\Models\Notification::create([
                'user_id' => $student->id,
                'message' => "Nouveau projet disponible : \"{$project->titre}\" est maintenant ouvert aux inscriptions.",
                'type' => 'info',
                'canal' => 'app',
                'lu' => false,
            ]);
        }

        return response()->json(['message' => 'Projet validé et étudiants notifiés', 'project' => $project]);
    }

    // Refuser un projet (RUP Projet)
    public function refuser(Request $request, Project $project)
    {
        $user = request()->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        if ($project->statut !== 'soumis') {
            return response()->json(['message' => 'Seul un projet soumis peut être refusé'], 422);
        }

        $request->validate(['motif_refus' => 'required|string']);
        $project->update([
            'statut' => 'refuse',
            'motif_refus' => $request->motif_refus,
        ]);

        // Notification pour le professeur
        \App\Models\Notification::create([
            'user_id' => $project->superviseur_id,
            'message' => "Votre projet \"{$project->titre}\" a été refusé. Motif : {$request->motif_refus}",
            'type' => 'danger',
            'canal' => 'app',
            'lu' => false,
        ]);

        return response()->json(['message' => 'Projet refusé', 'project' => $project]);
    }

    // Afficher un projet
    public function show(Project $project)
    {
        $user = request()->user();
        
        // Vérification des droits
        if ($user->role === 'professeur' && $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        if ($user->role === 'etudiant') {
            if ($project->statut !== 'valide') {
                return response()->json(['message' => 'Projet non disponible'], 403);
            }
            // ✅ CORRIGÉ : Permettre l'accès si spécialité correspond OU si Mélangé
            if ($project->specialite_id !== null && $project->specialite_id !== $user->specialite_id) {
                return response()->json(['message' => 'Ce projet n\'est pas dans votre spécialité'], 403);
            }
        }
        if ($user->role === 'rup_specialite') {
            // ✅ CORRIGÉ : Permettre l'accès si spécialité correspond OU si Mélangé
            if ($project->specialite_id !== null && $project->specialite_id !== $user->specialite_id) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }
        }
        
        return response()->json($project->load('superviseur', 'specialite', 'groupes', 'groupes.membres', 'targetedStudents'));
    }

        // Archiver un projet
// Liste des projets archivés
       
        // Archiver un projet
        public function archive(Project $project)
        {
            $user = request()->user();
            if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }
            $project->update(['est_archive' => true]);
            return response()->json(['message' => 'Projet archivé avec succès']);
        }

        // Restaurer un projet
        public function restore(Project $project)
        {
            $user = request()->user();
            if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }
            $project->update(['est_archive' => false]);
            return response()->json(['message' => 'Projet restauré avec succès']);
        }

        // Supprimer définitivement un projet
        public function forceDelete(Project $project)
        {
            $user = request()->user();
            if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
                return response()->json(['message' => 'Accès non autorisé'], 403);
            }
            // Vérifier qu'il n'y a pas de groupes ou rapports associés
            if ($project->groupes()->count() > 0) {
                return response()->json(['message' => 'Impossible de supprimer un projet qui a des groupes'], 422);
            }
            
            $project->forceDelete();
            return response()->json(['message' => 'Projet supprimé définitivement']);
        }
        public function myArchived(Request $request)
{
                $user = $request->user();
                
                if ($user->role !== 'professeur') {
                    return response()->json(['message' => 'Non autorisé'], 403);
                }
                
                $projects = Project::where('est_archive', true)
                    ->where('superviseur_id', $user->id)
                    ->with('superviseur', 'specialite')
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                return response()->json($projects);
            }
}