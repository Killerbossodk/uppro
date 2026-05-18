<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompteRendu;
use App\Models\Meeting;
use Illuminate\Http\Request;
use App\Services\GeminiService;

class CompteRenduController extends Controller
{
    // Créer ou mettre à jour le compte rendu d'un meeting (professeur uniquement)
    public function store(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        if ($user->role !== 'professeur' || $meeting->organisateur_id !== $user->id) {
            return response()->json(['message' => 'Seul le professeur organisateur peut rédiger le compte rendu'], 403);
        }

        // Validation simplifiée : accepte un tableau d'objets avec 'titre' et 'contenu'
        $validated = $request->validate([
            'contenu' => 'required|array',
            'contenu.*.titre' => 'nullable|string',
            'contenu.*.contenu' => 'required|string',
            'avancement_pct' => 'nullable|integer|min:0|max:100',
        ]);

        // Conversion au format de stockage (avec un type déduit)
        $contenuStock = array_map(function($bloc) {
            // Si le bloc a un titre non vide, on le considère comme un titre, sinon comme un point
            $type = (!empty($bloc['titre'])) ? 'titre' : 'point';
            return [
                'type' => $type,
                'contenu' => $bloc['contenu'],
                'titre' => $bloc['titre'] ?? null,
            ];
        }, $validated['contenu']);

        $compteRendu = CompteRendu::updateOrCreate(
            ['meeting_id' => $meeting->id],
            [
                'redacteur_id' => $user->id,
                'contenu' => $contenuStock,
                'avancement_pct' => $validated['avancement_pct'] ?? 0,
                'ia_brouillon' => $request->ia_brouillon ?? null,
            ]
        );

        // Générer des notifications pour les actions (détection par mot-clé)
        $actions = collect($contenuStock)->filter(fn($bloc) => 
            stripos($bloc['contenu'], 'action') !== false || $bloc['type'] === 'action'
        );
        if ($actions->count()) {
            foreach ($meeting->groupes as $group) {
                foreach ($group->membres as $membre) {
                    foreach ($actions as $action) {
                        \App\Models\Notification::create([
                            'user_id' => $membre->id,
                            'message' => "Action à faire: {$action['contenu']} (suite au RDV du {$meeting->date_heure})",
                            'type' => 'alerte',
                            'canal' => 'app',
                            'lu' => false,
                        ]);
                    }
                }
            }
        }

        return response()->json($compteRendu->load('meeting', 'redacteur'), 201);
    }

    // Récupérer le compte rendu d'un meeting
    public function show(Meeting $meeting)
{
    $user = request()->user();
    
    // Vérifier les droits d'accès
    $authorized = false;
    if ($user->role === 'professeur' && $meeting->organisateur_id === $user->id) {
        $authorized = true;
    } elseif ($user->role === 'etudiant') {
        $authorized = $meeting->groupes()
            ->whereHas('membres', fn($q) => $q->where('user_id', $user->id))
            ->exists();
    } elseif (in_array($user->role, ['rup_projet', 'rup_specialite'])) {
        $authorized = true;
    }

    if (!$authorized) {
        return response()->json(['message' => 'Accès non autorisé'], 403);
    }

    $compteRendu = CompteRendu::where('meeting_id', $meeting->id)->first();
    
    if (!$compteRendu) {
        return response()->json(['message' => 'Aucun compte rendu pour ce RDV'], 404);
    }
    
    return response()->json($compteRendu);
}
    // Générer un brouillon IA (appel à Gemini)
    public function generateDraft(Meeting $meeting, GeminiService $gemini)
    {
        $user = request()->user();
        if ($user->role !== 'professeur' || $meeting->organisateur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $meeting->load('groupes.membres');
        $draft = $gemini->generateMeetingMinutes($meeting);
        return response()->json(['draft' => $draft]);
    }
}