<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Grade;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TextExtractorService;
use App\Services\GeminiService;

class GradeController extends Controller
{
    /**
     * Saisir ou modifier une note (professeur du jury ou RUP Spécialité)
     */
    public function storeOrUpdate(Request $request, Meeting $meeting)
    {
        if ($meeting->type !== 'soutenance') {
            return response()->json(['message' => 'Seule une soutenance peut avoir une note'], 422);
        }

        $user = $request->user();
        $jury = $meeting->jury;

        $isOrganizer     = $meeting->organisateur_id === $user->id;
        $isPresident     = $jury && $jury->president_id === $user->id;
        $isMemberOfJury  = $jury && $jury->membres()->where('user_id', $user->id)->exists();
        $isAdmin         = $user->role === 'admin';

        if (!$isOrganizer && !$isPresident && !$isMemberOfJury && !$isAdmin) {
            return response()->json(['message' => 'Accès non autorisé : seul le jury de cette soutenance peut attribuer des notes'], 403);
        }

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'note' => 'required|numeric|min:0|max:20',
            'commentaire' => 'nullable|string',
        ]);

        $grade = Grade::firstOrNew([
            'meeting_id' => $meeting->id,
            'group_id' => $validated['group_id']
        ]);
        
        $grade->evaluateur_id = $user->id;
        $grade->note = $validated['note'];
        $grade->commentaire = $validated['commentaire'] ?? null;
        $grade->save();

        // Diffuser en temps réel aux autres membres du jury
        broadcast(new \App\Events\GradeUpdated(
            $meeting->id, 
            $validated['group_id'], 
            $grade->note, 
            $grade->commentaire
        ))->toOthers();

        return response()->json($grade);
    }

    /**
     * Valider la note via le paramètre Meeting (RUP Spécialité)
     */
    public function validateGradeByMeeting(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        
        if ($user->role !== 'rup_specialite') {
            return response()->json(['message' => 'Seul le RUP Spécialité peut valider la note'], 403);
        }
        if ($meeting->project->specialite_id !== $user->specialite_id) {
            return response()->json(['message' => 'Ce projet n\'est pas dans votre spécialité'], 403);
        }

        $grade = Grade::where('meeting_id', $meeting->id)->first();
        if (!$grade) {
            return response()->json(['message' => 'Aucune note saisie pour cette soutenance'], 422);
        }

        $grade->valide_par_rup = true;
        $grade->save();

        return response()->json(['message' => 'Note validée']);
    }

    /**
     * Valider la note via le paramètre Grade (RUP Spécialité)
     */
    public function validateGrade(Request $request, Grade $grade)
    {
        $user = $request->user();
        
        if ($user->role !== 'rup_specialite') {
            return response()->json(['message' => 'Seul le RUP Spécialité peut valider la note'], 403);
        }
        if ($grade->meeting->project->specialite_id !== $user->specialite_id) {
            return response()->json(['message' => 'Ce projet n\'est pas dans votre spécialité'], 403);
        }

        $grade->valide_par_rup = true;
        $grade->save();

        return response()->json(['message' => 'Note validée']);
    }

    /**
     * Publier la note via le paramètre Meeting (RUP Spécialité)
     */
    public function publishByMeeting(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        
        if ($user->role !== 'rup_specialite') {
            return response()->json(['message' => 'Seul le RUP Spécialité peut publier la note'], 403);
        }
        if ($meeting->project->specialite_id !== $user->specialite_id) {
            return response()->json(['message' => 'Ce projet n\'est pas dans votre spécialité'], 403);
        }

        $grade = Grade::where('meeting_id', $meeting->id)->first();
        if (!$grade || !$grade->valide_par_rup) {
            return response()->json(['message' => 'La note n\'a pas encore été validée'], 422);
        }

        // Vérifier que le rapport corrigé a été déposé pour au moins un groupe
        $groups = $meeting->groupes;
        $hasCorrectedReport = false;
        foreach ($groups as $group) {
            $report = Report::where('group_id', $group->id)
                            ->where('type', 'corrige')
                            ->exists();
            if ($report) {
                $hasCorrectedReport = true;
                break;
            }
        }
        if (!$hasCorrectedReport) {
            return response()->json(['message' => 'Le rapport corrigé n\'a pas encore été déposé'], 422);
        }

        $grade->publiee = true;
        $grade->save();

        // 📧 Notification App
        foreach ($meeting->groupes as $group) {
            foreach ($group->membres as $membre) {
                \App\Models\Notification::create([
                    'user_id' => $membre->id,
                    'message' => "La note et les critiques pour la soutenance '{$meeting->titre}' ont été publiées.",
                    'type' => 'grade',
                    'canal' => 'app',
                    'lu' => false,
                    'data' => json_encode(['meeting_id' => $meeting->id, 'is_grade' => true])
                ]);
            }
        }

        return response()->json(['message' => 'Note publiée']);
    }

    /**
     * Publier la note via le paramètre Grade (RUP Spécialité)
     */
    public function publish(Request $request, Grade $grade)
    {
        $user = $request->user();
        
        if ($user->role !== 'rup_specialite') {
            return response()->json(['message' => 'Seul le RUP Spécialité peut publier la note'], 403);
        }
        if ($grade->meeting->project->specialite_id !== $user->specialite_id) {
            return response()->json(['message' => 'Ce projet n\'est pas dans votre spécialité'], 403);
        }

        if (!$grade->valide_par_rup) {
            return response()->json(['message' => 'La note n\'a pas encore été validée'], 422);
        }

        // Vérifier que le rapport corrigé a été déposé
        $meeting = $grade->meeting;
        $groups = $meeting->groupes;
        $hasCorrectedReport = false;
        foreach ($groups as $group) {
            $report = Report::where('group_id', $group->id)
                            ->where('type', 'corrige')
                            ->exists();
            if ($report) {
                $hasCorrectedReport = true;
                break;
            }
        }
        if (!$hasCorrectedReport) {
            return response()->json(['message' => 'Le rapport corrigé n\'a pas encore été déposé'], 422);
        }

        $grade->publiee = true;
        $grade->save();

        // 📧 Notification App
        foreach ($meeting->groupes as $group) {
            foreach ($group->membres as $membre) {
                \App\Models\Notification::create([
                    'user_id' => $membre->id,
                    'message' => "La note et les critiques pour la soutenance '{$meeting->titre}' ont été publiées.",
                    'type' => 'grade',
                    'canal' => 'app',
                    'lu' => false,
                    'data' => json_encode(['meeting_id' => $meeting->id, 'is_grade' => true])
                ]);
            }
        }

        return response()->json(['message' => 'Note publiée']);
    }

    /**
     * Consulter la note via Meeting (étudiant : seulement si publiée)
     */
    public function showByMeeting(Meeting $meeting)
    {
        $user = request()->user();
        $grade = Grade::where('meeting_id', $meeting->id)->first();
        
        if (!$grade) {
            return response()->json(['message' => 'Aucune note disponible'], 404);
        }
        if ($user->role === 'etudiant' && !$grade->publiee) {
            return response()->json(['message' => 'Note non encore publiée'], 403);
        }
        
        return response()->json($grade);
    }

    /**
     * Consulter la note via Grade (étudiant : seulement si publiée)
     */
    public function show(Grade $grade)
    {
        $user = request()->user();
        
        if ($user->role === 'etudiant' && !$grade->publiee) {
            return response()->json(['message' => 'Note non encore publiée'], 403);
        }
        
        return response()->json($grade);
    }

    /**
     * Suggérer une note via IA
     */
    public function suggestGrade(Meeting $meeting, GeminiService $gemini)
    {
        $user = request()->user();
        
        if (!in_array($user->role, ['professeur', 'rup_specialite'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Récupérer le dernier rapport déposé par un groupe de cette soutenance
        $group = $meeting->groupes->first();
        if (!$group) {
            return response()->json(['message' => 'Aucun groupe associé à cette soutenance'], 404);
        }
        
        $report = $group->reports()->latest()->first();
        if (!$report) {
            return response()->json(['message' => 'Aucun rapport trouvé'], 404);
        }

        // Nettoyer le chemin (enlever /storage/ ou storage/ au début si présent)
        $filePath = $report->fichier_url;
        $cleanedPath = ltrim($filePath, '/');
        if (str_starts_with($cleanedPath, 'storage/')) {
            $cleanedPath = substr($cleanedPath, 8);
        }
        
        $extension = strtolower(pathinfo($cleanedPath, PATHINFO_EXTENSION));
        $fullPath = storage_path('app/public/' . $cleanedPath);

        $content = '';
        $fileData = null;

        if ($extension === 'pdf' && file_exists($fullPath)) {
            $fileData = [
                'mime_type' => 'application/pdf',
                'data' => base64_encode(file_get_contents($fullPath))
            ];
        } else {
            $extractor = new TextExtractorService();
            $content = $extractor->extract($cleanedPath);
        }

        $suggestion = $gemini->suggestGrade($content, $fileData);
        
        return response()->json(['suggestion' => $suggestion]);
    }
}