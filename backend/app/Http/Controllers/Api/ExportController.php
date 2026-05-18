<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\ProjectsExport;
use App\Exports\GroupsExport;
use App\Exports\GradesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;


class ExportController extends Controller
{
    /**
     * Export des projets en Excel
     */
    public function exportProjects(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['annee', 'specialite_id', 'include_archived']);
        
        $export = new ProjectsExport(
            $user->role, 
            $user->id, 
            $user->specialite_id, 
            $filters
        );
        
        return Excel::download($export, 'projets-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export des groupes en Excel
     */
    public function exportGroups(Request $request)
    {
        $user = $request->user();
        
        $export = new GroupsExport(
            $user->role,
            $user->id,
            $user->specialite_id
        );
        
        return Excel::download($export, 'groupes-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export des notes en Excel
     */
    public function exportGrades(Request $request)
    {
        $user = $request->user();
        
        $export = new GradesExport(
            $user->role,
            $user->specialite_id
        );
        
        return Excel::download($export, 'notes-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export PDF des statistiques
     */
    public function exportAnalyticsPDF(Request $request)
    {
        $user = $request->user();
        
        $controller = new \App\Http\Controllers\Api\AnalyticsController();
        $response = $controller->index($request);
        $stats = json_decode($response->getContent());
        
        $pdf = Pdf::loadView('exports.analytics', [
            'stats' => $stats,
            'user' => $user,
            'date' => now()->format('d/m/Y')
        ]);
        
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download('statistiques-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export PDF de la liste des projets
     */
    public function exportProjectsPDF(Request $request)
    {
        $user = $request->user();
        
        $query = \App\Models\Project::with('superviseur', 'specialite', 'groupes');
        
        if ($user->role === 'professeur') {
            $query->where('superviseur_id', $user->id);
        } elseif ($user->role === 'rup_specialite') {
            $query->where(function ($q) use ($user) {
                $q->where('specialite_id', $user->specialite_id)
                  ->orWhereNull('specialite_id');
            });
        }
        
        $projects = $query->where('est_archive', false)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $pdf = Pdf::loadView('exports.projects-pdf', [
            'projects' => $projects,
            'user' => $user,
            'date' => now()->format('d/m/Y')
        ]);
        
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('liste-projets-' . now()->format('Y-m-d') . '.pdf');
    }

}