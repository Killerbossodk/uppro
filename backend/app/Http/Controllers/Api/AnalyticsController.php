<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Report;
use App\Models\Grade;
use App\Models\Meeting;
use App\Models\Specialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['rup_projet', 'rup_specialite'])) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $query = Project::query();
        
        // Filtres
        if ($request->has('annee') && $request->annee !== 'all') {
            $query->where('annee_universitaire', $request->annee);
        }
        
        if ($request->has('specialite_id') && $request->specialite_id !== 'all') {
            $query->where('specialite_id', $request->specialite_id);
        }
        
        // Pour RUP Spécialité, filtrer automatiquement
        if ($user->role === 'rup_specialite') {
            $query->where(function ($q) use ($user) {
                $q->where('specialite_id', $user->specialite_id)
                  ->orWhereNull('specialite_id');
            });
        }

        $stats = [];

        // 1. Projets par statut
        $stats['projets_par_statut'] = (clone $query)
            ->select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get();

        // 2. Projets par spécialité
        $stats['projets_par_specialite'] = Specialite::withCount(['projects' => function ($q) use ($query, $request) {
            if ($request->has('annee') && $request->annee !== 'all') {
                $q->where('annee_universitaire', $request->annee);
            }
        }])->get()->map(function ($s) {
            return [
                'nom' => $s->nom,
                'projects_count' => $s->projects_count
            ];
        });

        // 3. Taux de dépôt
        $totalRapports = Report::count();
        $rapportsSoumis = Report::where('statut', 'soumis')->count();
        $stats['taux_depot'] = $totalRapports > 0 ? round(($rapportsSoumis / $totalRapports) * 100, 2) : 0;
        $stats['rapports_soumis'] = $rapportsSoumis;

        // 4. Moyenne des notes
        $stats['moyenne_notes'] = Grade::where('publiee', true)
            ->join('meetings', 'grades.meeting_id', '=', 'meetings.id')
            ->join('projects', 'meetings.project_id', '=', 'projects.id')
            ->join('specialites', 'projects.specialite_id', '=', 'specialites.id')
            ->select('specialites.nom', DB::raw('avg(grades.note) as moyenne'))
            ->groupBy('specialites.nom')
            ->get();

        // 5. Activité récente
        $stats['activite_recente'] = Project::where('created_at', '>=', now()->subDays(30))->count();

        // 6. Top professeurs
        $stats['top_professeurs'] = Project::select('users.name', 'users.prenom', DB::raw('count(*) as total'))
            ->join('users', 'projects.superviseur_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.prenom')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 7. Activités récentes (timeline)
        $stats['activites_recentes'] = $this->getRecentActivities();

        return response()->json($stats);
    }

    private function getRecentActivities()
    {
        $activites = [];
        
        // Derniers projets créés
        $recentProjects = Project::with('superviseur')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentProjects as $p) {
            $activites[] = [
                'type' => 'projet_cree',
                'description' => "Nouveau projet : {$p->titre} par {$p->superviseur->name}",
                'date' => $p->created_at
            ];
        }
        
        // Dernières soutenances
        $recentSoutenances = Meeting::where('type', 'soutenance')
            ->with('project')
            ->orderBy('date_heure', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentSoutenances as $s) {
            $activites[] = [
                'type' => 'soutenance',
                'description' => "Soutenance : {$s->titre}",
                'date' => $s->date_heure
            ];
        }
        
        // Trier par date
        usort($activites, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        
        return array_slice($activites, 0, 10);
    }
}