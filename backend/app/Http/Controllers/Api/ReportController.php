<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Group;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\GeminiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Liste des rapports (selon rôle)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Report::with('group', 'deposant');

        if ($user->role === 'professeur') {
            $query->whereHas('group.project', fn($q) => $q->where('superviseur_id', $user->id));
        } elseif ($user->role === 'etudiant') {
            $query->whereHas('group.membres', fn($q) => $q->where('user_id', $user->id));
        } elseif ($user->role === 'rup_specialite') {
            $query->whereHas('group.project', function ($q) use ($user) {
                $q->where('specialite_id', $user->specialite_id)
                  ->orWhereNull('specialite_id');
            });
        }

        return response()->json($query->get());
    }

    /**
     * Déposer un rapport
     */
    /**
 * Déposer un rapport
 */
public function store(Request $request)
{
    $user = $request->user();
    if ($user->role !== 'etudiant') {
        return response()->json(['message' => 'Seul un étudiant peut déposer un rapport'], 403);
    }

    $validated = $request->validate([
        'group_id' => 'required|exists:groups,id',
        'titre' => 'required|string|max:200',
        'fichier' => 'required|file|mimes:pdf,doc,docx|max:10240',
        'type' => 'required|in:intermediaire,final,corrige',
    ]);

    $group = Group::with('membres')->find($validated['group_id']);

    $membre = $group->membres()->where('user_id', $user->id)->first();
    if (!$membre) {
        return response()->json(['message' => 'Vous n\'êtes pas membre de ce groupe'], 403);
    }

    $isChef = $membre->pivot->est_chef;
    $isDelegue = DB::table('group_user')
        ->where('group_id', $group->id)
        ->where('user_id', $user->id)
        ->whereNotNull('chef_delegue_id')
        ->exists();

    if (!$isChef && !$isDelegue) {
        return response()->json(['message' => 'Seul le chef ou un délégué peut déposer un rapport'], 403);
    }

    $lastReport = Report::where('group_id', $group->id)
                        ->where('type', $validated['type'])
                        ->latest('created_at')
                        ->first();
    $newVersion = $lastReport ? $lastReport->version + 1 : 1;

    // ✅ CORRECTION ICI : stockage avec chemin relatif
    $path = $request->file('fichier')->store('reports', 'public');
    $url = '/storage/' . $path;  // ← Changement important !

    $report = Report::create([
        'group_id' => $group->id,
        'deposant_id' => $user->id,
        'titre' => $validated['titre'],
        'fichier_url' => $url,
        'version' => $newVersion,
        'type' => $validated['type'],
        'statut' => 'soumis',
    ]);

    // Analyse IA asynchrone
    dispatch(new \App\Jobs\AnalyzeReportJob($report));

    // Notifier le professeur
    Notification::create([
        'user_id' => $group->project->superviseur_id,
        'message' => "Le groupe {$group->nom} a déposé un rapport '{$report->titre}' (version {$newVersion})",
        'type' => 'rapport',
        'canal' => 'app',
        'lu' => false,
    ]);

    return response()->json([
        'message' => 'Rapport déposé avec succès',
        'report' => $report->load('deposant'),
    ], 201);
}
    /**
     * Voir un rapport
     */
    public function show(Report $report)
    {
        $user = request()->user();
        $group = $report->group;

        if ($user->role === 'professeur' && $group->project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        if ($user->role === 'etudiant' && !$group->membres()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        if ($user->role === 'rup_specialite' && $group->project->specialite_id !== $user->specialite_id && $group->project->specialite_id !== null) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json($report->load('group.project', 'deposant'));
    }

    /**
     * Exporter le rapport en PDF
     */
    public function exportReportPdf(Report $report)
    {
        $this->authorizeAccess($report);

        $report->load(['group.project.superviseur', 'deposant', 'comments.user']);

        $pdf = Pdf::loadView('pdf.report_pdf', [
            'report' => $report,
        ]);

        $pdf->setPaper('A4');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download('rapport-' . Str::slug($report->titre) . '.pdf');
    }

    /**
     * Déléguer le dépôt (chef)
     */
    public function delegate(Request $request, Group $group)
    {
        $user = $request->user();
        if ($user->role !== 'etudiant') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $membre = $group->membres()->where('user_id', $user->id)->first();
        if (!$membre || !$membre->pivot->est_chef) {
            return response()->json(['message' => 'Seul le chef peut déléguer'], 403);
        }

        $validated = $request->validate(['user_id' => 'required|exists:users,id']);
        $delegue = User::find($validated['user_id']);
        if (!$group->membres()->where('user_id', $delegue->id)->exists()) {
            return response()->json(['message' => 'Cet étudiant n\'est pas membre du groupe'], 422);
        }

        DB::table('group_user')
            ->where('group_id', $group->id)
            ->where('user_id', $delegue->id)
            ->update(['chef_delegue_id' => $user->id]);

        return response()->json(['message' => "Délégation accordée à {$delegue->name}"]);
    }

    /**
     * Valider un rapport
     */
    public function validateReport(Request $request, Report $report)
{
    $user = $request->user();
    
    // 🔍 LOG ULTIME
    \Log::info('========== VALIDATION RAPPORT ==========');
    \Log::info('1. User ID: ' . ($user ? $user->id : 'null'));
    \Log::info('2. User Role: ' . ($user ? $user->role : 'null'));
    \Log::info('3. Report ID: ' . $report->id);
    \Log::info('4. Report statut: ' . $report->statut);
    \Log::info('5. Group ID: ' . $report->group_id);
    \Log::info('6. Projet superviseur_id: ' . $report->group->project->superviseur_id);
    
    if (!$user) {
        \Log::info('7. Bloqué: utilisateur non authentifié');
        return response()->json(['message' => 'Non authentifié'], 401);
    }
    \Log::info('Tentative validation rapport', [
        'user_id' => $user->id,
        'user_role' => $user->role,
        'report_id' => $report->id,
        'superviseur_id' => $report->group->project->superviseur_id ?? 'NULL'
    ]);

    $isProfessor = ($user->role === 'professeur' && $report->group->project->superviseur_id == $user->id);
    $isRupSpecialite = ($user->role === 'rup_specialite');
    $isRupProjet = ($user->role === 'rup_projet');

    if (!$isProfessor && !$isRupSpecialite && !$isRupProjet) {
        return response()->json([
            'message' => 'Accès non autorisé',
            'debug' => [
                'votre_id' => $user->id,
                'superviseur_id' => $report->group->project->superviseur_id ?? 'NULL',
                'votre_role' => $user->role
            ]
        ], 403);
    }
    
    if ($report->statut !== 'soumis') {
        \Log::info('12. BLOQUÉ: rapport non soumis (statut: ' . $report->statut . ')');
        return response()->json(['message' => 'Ce rapport ne peut pas être validé'], 422);
    }
    
    \Log::info('13. ✅ VALIDATION ACCEPTÉE');
    $report->update(['statut' => 'valide']);
    
    // 📧 Notification Email + App
    foreach ($report->group->membres as $membre) {
        $membre->notify(new \App\Notifications\ReportStatusChanged($report, 'valide'));
    }
    
    return response()->json(['message' => 'Rapport validé']);
}
    /**
     * Rejeter un rapport avec feedback
     */
    public function rejectReport(Request $request, Report $report)
{
    // ✅ Utiliser $request->user() au lieu de request()->user()
    $user = $request->user();
    
    // 🔍 Debug temporaire
    \Log::info('rejectReport - User:', [
        'user_id' => $user ? $user->id : 'null',
        'user_role' => $user ? $user->role : 'null',
        'superviseur_id' => $report->group->project->superviseur_id
    ]);
    
    // ✅ Vérifier que l'utilisateur est bien connecté
    if (!$user) {
        return response()->json(['message' => 'Non authentifié'], 401);
    }
    
    $isProfessor = ($user->role === 'professeur' && $report->group->project->superviseur_id === $user->id);
    $isRupSpecialite = ($user->role === 'rup_specialite');
    $isRupProjet = ($user->role === 'rup_projet');
    
    if (!$isProfessor && !$isRupSpecialite && !$isRupProjet) {
        return response()->json([
            'message' => 'Accès non autorisé',
            'debug' => [
                'user_role' => $user->role,
                'user_id' => $user->id,
                'superviseur_id' => $report->group->project->superviseur_id
            ]
        ], 403);
    }

    $validated = $request->validate(['feedback_ia' => 'required|string']);
    
    $report->update([
        'statut' => 'rejete',
        'feedback_ia' => $validated['feedback_ia'],
    ]);

    foreach ($report->group->membres as $membre) {
        // Envoi via l'App (existant) et par Email
        $membre->notify(new \App\Notifications\ReportStatusChanged($report, 'rejeté'));
    }

    return response()->json(['message' => 'Rapport rejeté']);
}
    /**
     * Rapports d'un groupe
     */
    public function groupReports(Group $group)
    {
        $user = request()->user();
        if ($user->role === 'etudiant' && !$group->membres()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        if ($user->role === 'professeur' && $group->project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json($group->reports()->with('deposant')->orderBy('version', 'desc')->get());
    }

    /**
     * Supprimer un rapport
     */
    public function destroy(Report $report)
    {
        $user = request()->user();
        $group = $report->group;

        $isChef = $group->membres()->where('user_id', $user->id)->wherePivot('est_chef', true)->exists();
        $isProfEncadrant = $user->role === 'professeur' && $group->project->superviseur_id === $user->id;
        $isRup = in_array($user->role, ['rup_projet', 'rup_specialite']);

        if (!$isChef && !$isProfEncadrant && !$isRup) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($report->fichier_url) {
            $path = str_replace('/storage/', 'public/', $report->fichier_url);
            Storage::delete($path);
        }

        $report->delete();
        return response()->json(['message' => 'Rapport supprimé']);
    }

    /**
     * Rapport au RUP (génération IA)
     */
    public function reportToRup(Group $group, GeminiService $gemini)
    {
        $user = request()->user();
        $project = $group->project;

        if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $meetings = $group->meetings()->with('compteRendu')->orderBy('date_heure')->get();
        $reports = $group->reports()->orderBy('version', 'desc')->get();
        $phases = $group->phases()->orderBy('date_debut')->get();
        $chef = $group->membres()->wherePivot('est_chef', true)->first();
        $nbMembres = $group->membres()->count();

        $summary = "=== RAPPORT DE SUIVI - GROUPE {$group->nom} ===\n\n";
        $summary .= "**Projet :** {$project->titre}\n";
        $summary .= "**Encadrant :** {$user->name} {$user->prenom}\n";
        $summary .= "**Chef de groupe :** " . ($chef ? "{$chef->name} {$chef->prenom}" : "Non désigné") . "\n";
        $summary .= "**Nombre de membres :** {$nbMembres}\n\n";

        if ($phases->isNotEmpty()) {
            $summary .= "--- AVANCEMENT DES PHASES ---\n";
            foreach ($phases as $phase) {
                $summary .= "• {$phase->titre} : {$phase->avancement_pct}% ";
                $summary .= "(" . $phase->date_debut->format('d/m/Y') . " - " . $phase->date_fin->format('d/m/Y') . ")\n";
            }
            $summary .= "\n";
        }

        $summary .= "--- RENDEZ-VOUS DE SUIVI ---\n";
        if ($meetings->isEmpty()) {
            $summary .= "Aucun rendez-vous enregistré.\n";
        } else {
            foreach ($meetings as $m) {
                $summary .= "• " . $m->date_heure->format('d/m/Y H:i') . " - {$m->titre}\n";
                if ($m->compteRendu) {
                    $contenu = is_array($m->compteRendu->contenu)
                        ? implode(' ', array_column($m->compteRendu->contenu, 'contenu'))
                        : $m->compteRendu->contenu;
                    $summary .= "  Résumé CR : " . Str::limit(strip_tags($contenu), 150) . "\n";
                }
            }
        }
        $summary .= "\n";

        $summary .= "--- RAPPORTS DÉPOSÉS ---\n";
        if ($reports->isEmpty()) {
            $summary .= "Aucun rapport déposé.\n";
        } else {
            foreach ($reports as $r) {
                $summary .= "• Version {$r->version} - {$r->type} : {$r->statut} (déposé le {$r->created_at->format('d/m/Y')})\n";
            }
        }
        $summary .= "\n";

        $prompt = "Tu es un professeur expérimenté qui rédige un rapport de synthèse formel destiné au RUP Projet. 
Le rapport doit être structuré avec les sections suivantes :
1. TITRE ET IDENTIFICATION
2. ÉTAT D'AVANCEMENT GLOBAL
3. POINTS FORTS
4. POINTS DE VIGILANCE
5. LIVRABLES
6. RECOMMANDATION
Utilise un ton neutre et professionnel. Base-toi uniquement sur les données fournies.\n\nDonnées collectées :\n" . $summary;

        $reportText = $gemini->callWithRetry($prompt, 2000);

        return response()->json([
            'group' => $group->nom,
            'project' => $project->titre,
            'report' => $reportText,
            'generated_at' => now()->toDateTimeString()
        ]);
    }

    /**
     * Enregistrer le rapport final édité et l'envoyer aux RUP
     */public function saveRupReport(Group $group, Request $request)
{
    $user = $request->user();
    $project = $group->project;

    if ($user->role !== 'professeur' || $project->superviseur_id !== $user->id) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $validated = $request->validate(['report' => 'required|string']);
    $reportContent = $validated['report'];

    $notificationData = json_encode([
        'report' => $reportContent,
        'group_id' => $group->id,
        'group_name' => $group->nom,
        'project' => $project->titre,
        'professor' => $user->name . ' ' . $user->prenom,
        'specialite_id' => $project->specialite_id,
    ], JSON_UNESCAPED_UNICODE);

    // ⭐ UNE SEULE notification pour le RUP Projet
    $rupProjet = User::where('role', 'rup_projet')->first();
    if ($rupProjet) {
        Notification::create([
            'user_id' => $rupProjet->id,
            'message' => "📋 Rapport de suivi du groupe {$group->nom} (projet {$project->titre}) envoyé par {$user->name} {$user->prenom}.",
            'type' => 'rup_report',
            'canal' => 'app',
            'lu' => false,
            'data' => $notificationData,
        ]);
    }

    // ⭐ UNE SEULE notification pour le RUP Spécialité concerné
    // (le premier trouvé, ou celui de la spécialité du projet)
    $rupSpecialite = null;
    if ($project->specialite_id) {
        $rupSpecialite = User::where('role', 'rup_specialite')
            ->where('specialite_id', $project->specialite_id)
            ->first();
    } else {
        // Pour Mélangé, prendre le premier RUP Spécialité dispo
        $rupSpecialite = User::where('role', 'rup_specialite')->first();
    }

    if ($rupSpecialite && $rupSpecialite->id !== ($rupProjet->id ?? 0)) {
        Notification::create([
            'user_id' => $rupSpecialite->id,
            'message' => "📋 Rapport de suivi (projet {$project->titre}) par {$user->name} {$user->prenom} – groupe {$group->nom}.",
            'type' => 'rup_report',
            'canal' => 'app',
            'lu' => false,
            'data' => $notificationData,
        ]);
    }

    return response()->json(['message' => 'Rapport envoyé avec succès aux RUP.']);
}
    /**
     * Récupérer les rapports de suivi pour les RUP
     */
    public function rupReports(Request $request)
{
    $user = $request->user();
    if (!in_array($user->role, ['rup_projet', 'rup_specialite'])) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    // ⭐ Filtrer par user_id pour ne voir que SES notifications
    $query = Notification::where('type', 'rup_report')
        ->where('user_id', $user->id)  // ← AJOUTER CETTE LIGNE
        ->orderBy('created_at', 'desc');

    $reports = $query->paginate(15);
    $reports->getCollection()->transform(function ($notification) {
        $notification->data = json_decode($notification->data);
        return $notification;
    });

    return response()->json($reports);
}
    /**
     * Vérifier l'accès en lecture
     */
    private function authorizeAccess(Report $report)
    {
        $user = request()->user();
        $group = $report->group;

        if ($user->role === 'professeur' && $group->project->superviseur_id !== $user->id) {
            abort(403, 'Accès non autorisé');
        }
        if ($user->role === 'etudiant' && !$group->membres()->where('user_id', $user->id)->exists()) {
            abort(403, 'Accès non autorisé');
        }
        if ($user->role === 'rup_specialite' && $group->project->specialite_id !== $user->specialite_id && $group->project->specialite_id !== null) {
            abort(403, 'Accès non autorisé');
        }
    }
    /**
 * Récupérer le contenu texte d'un rapport
 */
/**
 * Récupérer le contenu texte d'un rapport
 */
public function getTextContent(Report $report)
{
    try {
        $extractor = new \App\Services\TextExtractorService();
        $filename = basename($report->fichier_url);
        $content = $extractor->extract('reports/' . $filename);
        
        if (empty($content)) {
            return response()->json([
                'text' => 'Aucun contenu texte trouvé dans ce fichier.',
                'length' => 0
            ]);
        }
        
        return response()->json([
            'text' => $content,
            'length' => strlen($content)
        ]);
    } catch (\Exception $e) {
        \Log::error('Erreur getTextContent: ' . $e->getMessage());
        return response()->json([
            'text' => 'Erreur lors de l\'extraction du texte.',
            'error' => $e->getMessage()
        ], 500);
    }
}


}