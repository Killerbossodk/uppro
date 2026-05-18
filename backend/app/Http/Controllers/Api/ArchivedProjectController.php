<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ArchivedProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Project::where('est_archive', true)
            ->with('superviseur', 'specialite')
            ->orderBy('annee_universitaire', 'desc')
            ->orderBy('created_at', 'desc');

        // Filtrer selon le rôle
        if ($user->role === 'professeur') {
            $query->where('superviseur_id', $user->id);
        } elseif ($user->role === 'rup_specialite') {
            // ✅ CORRIGÉ : Inclure les projets Mélangé
            $query->where(function ($q) use ($user) {
                $q->where('specialite_id', $user->specialite_id)
                  ->orWhereNull('specialite_id');
            });
        } elseif ($user->role === 'etudiant') {
            // Les étudiants ne voient que les projets de leur spécialité ou Mélangé
            $query->where(function ($q) use ($user) {
                $q->where('specialite_id', $user->specialite_id)
                  ->orWhereNull('specialite_id');
            });
        }
        // rup_projet voit tout

        return response()->json($query->get());
    }

    public function show(Project $project)
    {
        if (!$project->est_archive) {
            return response()->json(['message' => 'Projet non archivé'], 404);
        }
        return response()->json($project->load('superviseur', 'specialite'));
    }
}