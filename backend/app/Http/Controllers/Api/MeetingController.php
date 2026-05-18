<?php

namespace App\Http\Controllers\Api;

use App\Notifications\MeetingReminder;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Group;
use App\Models\Project;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;


class MeetingController extends Controller
{
    // Liste des RDV (selon rôle)
public function index(Request $request)
{
    $user = $request->user();
    $query = Meeting::with(['project.specialite', 'organisateur', 'groupes']);

        // Par défaut, masquer les RDV terminés pour les étudiants/profs (disparaissent de l'agenda)
        // mais les laisser visibles pour les RUPs/Admins (pour validation/publication des notes) ou si demandé
        if (!$request->has('include_completed') && !in_array($user->role, ['rup_projet', 'rup_specialite', 'admin'])) {
            $query->where('statut_soutenance', '!=', 'termine');
        }

    if ($user->role === 'professeur') {
        $query->where(function ($q) use ($user) {
            $q->where('organisateur_id', $user->id)
              ->orWhereHas('jury', function ($jq) use ($user) {
                  $jq->where('president_id', $user->id)
                    ->orWhereHas('membres', fn($mq) => $mq->where('user_id', $user->id));
              });
        });
    } elseif ($user->role === 'etudiant') {
        $query->whereHas('groupes', function ($q) use ($user) {
            $q->whereHas('membres', fn($sq) => $sq->where('user_id', $user->id));
        });
    } elseif ($user->role === 'rup_specialite') {
        $query->whereHas('project', fn($q) => $q->where('specialite_id', $user->specialite_id));
    } elseif ($user->role === 'rup_projet') {
        // RUP Projet voit tout
    }

    return response()->json($query->orderBy('date_heure')->get());
}

    // Créer un RDV (professeur)
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['professeur', 'rup_projet', 'rup_specialite', 'rup', 'admin'])) {
            return response()->json(['message' => 'Non autorisé à créer un RDV'], 403);
        }

    $validated = $request->validate([
        'project_id' => 'required|exists:projects,id',
        'titre' => 'required|string|max:200',
        'ordre_du_jour' => 'nullable|string',
        'date_heure' => 'required|date',
        'lieu' => 'nullable|string|max:200',
        'type' => 'required|in:rdv_pilotage,soutenance,depot_rapport',
        'lien_visio' => 'nullable|url|max:500',
        'duree_minutes' => 'nullable|integer|min:1',
        'group_ids' => 'required|array',
        'group_ids.*' => 'exists:groups,id',
        'piece_jointe' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
    ]);

        $project = Project::find($validated['project_id']);
        if ($user->role === 'professeur' && $project->superviseur_id !== $user->id) {
            return response()->json(['message' => 'Vous n\'êtes pas le superviseur de ce projet'], 403);
        }

    $groups = Group::whereIn('id', $validated['group_ids'])->get();
    foreach ($groups as $group) {
        if ($group->project_id !== $project->id) {
            return response()->json(['message' => 'Un groupe n\'appartient pas à ce projet'], 422);
        }
    }

    DB::beginTransaction();
    try {
        $meeting = Meeting::create([
            'project_id' => $validated['project_id'],
            'organisateur_id' => $user->id,
            'titre' => $validated['titre'],
            'ordre_du_jour' => $validated['ordre_du_jour'] ?? null,
            'date_heure' => $validated['date_heure'],
            'lieu' => $validated['lieu'] ?? null,
            'type' => $validated['type'],
            'lien_visio' => $validated['lien_visio'] ?? null,
            'duree_minutes' => $validated['duree_minutes'] ?? 45,
        ]);

        if ($request->hasFile('piece_jointe')) {
            $path = $request->file('piece_jointe')->store('pieces_jointes', 'public');
            $meeting->piece_jointe = '/storage/' . $path;
            $meeting->save();
        }

        foreach ($validated['group_ids'] as $groupId) {
            $meeting->groupes()->attach($groupId, ['statut_reponse' => 'en_attente']);
        }

        // Notifications (sans email pour éviter les erreurs)
        foreach ($groups as $group) {
            foreach ($group->membres as $membre) {
                Notification::create([
                    'user_id' => $membre->id,
                    'message' => "Nouveau RDV: {$meeting->titre} le " . \Carbon\Carbon::parse($meeting->date_heure)->format('d/m/Y H:i'),
                    'type' => 'rdv',
                    'canal' => 'app',
                    'lu' => false,
                ]);
            }
        }

        DB::commit();
        return response()->json($meeting->load('project', 'organisateur', 'groupes'), 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Erreur lors de la création du RDV', 'error' => $e->getMessage()], 500);
    }
}
    // Répondre à un RDV (étudiant)
    public function respond(Request $request, Meeting $meeting)
{
    $user = $request->user();
    if (!$user || $user->role !== 'etudiant') {
        return response()->json(['message' => 'Seul un étudiant peut répondre'], 403);
    }

    if ($meeting->type === 'soutenance') {
        return response()->json(['message' => 'Les soutenances sont obligatoires et ne peuvent être refusées'], 422);
    }

    // Trouver le groupe de l'étudiant pour ce meeting
    $group = $meeting->groupes()->whereHas('membres', function ($q) use ($user) {
        $q->where('user_id', $user->id);
    })->first();

    if (!$group) {
        return response()->json(['message' => 'Vous n\'êtes pas invité à ce RDV'], 403);
    }

    $validated = $request->validate([
        'statut_reponse' => 'required|in:accepte,refuse',
        'motif_refus' => 'required_if:statut_reponse,refuse|string|nullable',
    ]);

    // ⭐ Mettre à jour la réponse pour ce groupe spécifique
    $meeting->groupes()->updateExistingPivot($group->id, [
        'statut_reponse' => $validated['statut_reponse'],
        'motif_refus' => $validated['motif_refus'] ?? null,
    ]);

    // ⭐ Notifier le professeur
    Notification::create([
        'user_id' => $meeting->organisateur_id,
        'message' => "{$user->name} {$user->prenom} a {$validated['statut_reponse']} le RDV '{$meeting->titre}' (groupe {$group->nom})",
        'type' => 'rdv',
        'canal' => 'app',
        'lu' => false,
        'data' => json_encode([
            'meeting_id' => $meeting->id,
            'student' => $user->name . ' ' . $user->prenom,
            'reponse' => $validated['statut_reponse'],
            'motif' => $validated['motif_refus'] ?? null,
        ]),
    ]);

    return response()->json([
        'message' => 'Réponse enregistrée',
        'statut' => $validated['statut_reponse'],
    ]);
}

    // Modifier un RDV (professeur organisateur)
    public function update(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        $isOrganizer = $user && $user->role === 'professeur' && $meeting->organisateur_id === $user->id;
        $isAdminOrRup = $user && in_array($user->role, ['rup_projet', 'rup_specialite', 'rup', 'admin']);
        
        if (!$isOrganizer && !$isAdminOrRup) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:200',
            'ordre_du_jour' => 'nullable|string',
            'date_heure' => 'sometimes|date',
            'lieu' => 'nullable|string|max:200',
            'lien_visio' => 'nullable|url|max:500',
        ]);

        $meeting->update($validated);

        foreach ($meeting->groupes as $group) {
            foreach ($group->membres as $membre) {
                Notification::create([
                    'user_id' => $membre->id,
                    'message' => "RDV modifié: {$meeting->titre} le {$meeting->date_heure->format('d/m/Y H:i')}",
                    'type' => 'rdv',
                    'canal' => 'app',
                    'lu' => false,
                ]);
            }
        }

        return response()->json($meeting);
    }

    // Annuler un RDV (professeur)
    public function destroy(Meeting $meeting)
    {
        $user = request()->user();
        $isOrganizer = $user && $user->role === 'professeur' && $meeting->organisateur_id === $user->id;
        $isAdminOrRup = $user && in_array($user->role, ['rup_specialite', 'rup', 'admin']);
        
        if (!$isOrganizer && !$isAdminOrRup) {
            return response()->json(['message' => 'Non autorisé. Le RUP Projet ne peut pas annuler une soutenance.'], 403);
        }

        $meeting->delete();
        return response()->json(['message' => 'RDV annulé']);
    }

    // Afficher un RDV
    public function show(Meeting $meeting)
{
    $user = request()->user();
    if (!$user) {
        return response()->json(['message' => 'Non authentifié'], 401);
    }

    if (in_array($user->role, ['rup_projet', 'rup_specialite', 'rup', 'admin'])) {
        // RUP and Admin have full access
    } elseif ($user->role === 'professeur') {
        $isOrganizer = (int)$meeting->organisateur_id === (int)$user->id;
        
        // On vérifie si le prof est dans le jury (président ou membre)
        $isJuryMember = false;
        if ($meeting->jury) {
            $isJuryMember = (int)$meeting->jury->president_id === (int)$user->id || 
                           $meeting->jury->membres()->where('user_id', $user->id)->exists();
        }
        
        if (!$isOrganizer && !$isJuryMember) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
    } elseif ($user->role === 'etudiant') {
        $hasGroup = $meeting->groupes()->whereHas('membres', fn($q) => $q->where('user_id', $user->id))->exists();
        if (!$hasGroup) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $grade = \App\Models\Grade::where('meeting_id', $meeting->id)->first();
        if (!$grade || !$grade->publiee) {
            $meeting->critiques_generales = "Vos résultats et critiques seront disponibles une fois publiés par l'administration.";
        }
    } else {
        return response()->json(['message' => 'Rôle non reconnu'], 403);
    }

    // ⭐ Convertir la date au format ISO pour le frontend
    $meeting->date_heure = \Carbon\Carbon::parse($meeting->date_heure)->toISOString();

    return response()->json($meeting->load('jury.president', 'jury.membres', 'project', 'organisateur', 'groupes.reports'));
}
    public function requestMeeting(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'etudiant') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'objet' => 'required|string|max:200',
            'message' => 'nullable|string',
            'creneaux_proposes' => 'nullable|array',
            'creneaux_proposes.*' => 'date',
        ]);

        $group = Group::findOrFail($validated['group_id']);
        
        if (!$group->membres()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Vous n\'êtes pas membre de ce groupe'], 403);
        }

        $professeur = $group->project->superviseur;
        if (!$professeur) {
            return response()->json(['message' => 'Aucun professeur encadrant trouvé'], 404);
        }

        $creneauxText = '';
        if (!empty($validated['creneaux_proposes'])) {
            $creneaux = collect($validated['creneaux_proposes'])->map(fn($d) => Carbon::parse($d)->format('d/m/Y H:i'));
            $creneauxText = "\nCréneaux proposés : " . $creneaux->join(', ');
        }

        Notification::create([
            'user_id' => $professeur->id,
            'message' => "📅 Demande de RDV de {$user->name} {$user->prenom} (groupe {$group->nom}) : {$validated['objet']}" .
                         ($validated['message'] ? "\nMessage : {$validated['message']}" : '') .
                         $creneauxText,
            'type' => 'rdv_request',
            'canal' => 'app',
            'lu' => false,
            'data' => json_encode([
                'group_id' => $group->id,
                'student_id' => $user->id,
                'creneaux' => $validated['creneaux_proposes'] ?? []
            ])
        ]);

        return response()->json(['message' => 'Demande de RDV envoyée au professeur']);
    }

    /**
     * Démarrer une soutenance
     */
    public function startSoutenance(Meeting $meeting)
    {
        $user = request()->user();
        $jury = $meeting->jury;

        $isPresident = $jury && $jury->president_id === $user->id;
        $isOrganizer = $meeting->organisateur_id === $user->id;

        $isAdminOrRup = $user && in_array($user->role, ['rup_specialite', 'rup', 'admin']);

        if (!$isPresident && !$isOrganizer && !$isAdminOrRup) {
            return response()->json(['message' => 'Seul le président du jury ou l\'organisateur peut démarrer la session'], 403);
        }

        $meeting->update([
            'statut_soutenance' => 'en_cours',
            'started_at' => now(),
        ]);

        broadcast(new \App\Events\SoutenanceStatusUpdated($meeting))->toOthers();
        
        return response()->json([
            'message' => 'La soutenance a commencé', 
            'statut' => 'en_cours',
            'started_at' => $meeting->started_at->toISOString()
        ]);
    }

    /**
     * Terminer une soutenance
     */
    public function finishSoutenance(Meeting $meeting)
    {
        $user = request()->user();
        $jury = $meeting->jury;

        $isPresident = $jury && $jury->president_id === $user->id;
        $isOrganizer = $meeting->organisateur_id === $user->id;

        $isAdminOrRup = $user && in_array($user->role, ['rup_specialite', 'rup', 'admin']);

        if (!$isPresident && !$isOrganizer && !$isAdminOrRup) {
            return response()->json(['message' => 'Seul le président du jury ou l\'organisateur peut clôturer la session'], 403);
        }

        $meeting->update([
            'statut_soutenance' => 'termine',
            'ended_at' => now(),
        ]);
        
        broadcast(new \App\Events\SoutenanceStatusUpdated($meeting))->toOthers();
        
        // On ne notifie plus les étudiants ici, le RUP Spécialité le fera en publiant la note

        // Notifier le RUP Projet
        $rupProjet = User::where('role', 'rup_projet')->first(); // Ou filtrer par projet si nécessaire
        if ($rupProjet) {
            Notification::create([
                'user_id' => $rupProjet->id,
                'message' => "Soutenance terminée : {$meeting->titre}. En attente de transmission au RUP Spé.",
                'type' => 'grade',
                'canal' => 'app',
                'lu' => false,
            ]);
        }

        return response()->json([
            'message' => 'La soutenance est terminée', 
            'statut' => 'termine',
            'ended_at' => $meeting->ended_at->toISOString()
        ]);
    }

    /**
     * Sauvegarder les critiques générales du jury
     */
    public function saveCritiques(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        $jury = $meeting->jury;

        $isPresident = $jury && $jury->president_id === $user->id;
        $isMemberOfJury = $jury && $jury->membres()->where('user_id', $user->id)->exists();

        if (!$isPresident && !$isMemberOfJury && !in_array($user->role, ['admin', 'rup_projet'])) {
            return response()->json(['message' => 'Seul un membre du jury peut modifier les observations générales.'], 403);
        }

        $validated = $request->validate([
            'critiques' => 'nullable|string',
        ]);

        $meeting->update(['critiques_generales' => $validated['critiques']]);

        // Diffuser l'événement en temps réel (WebSocket Reverb)
        broadcast(new \App\Events\CritiquesUpdated($meeting))->toOthers();

        return response()->json(['message' => 'Critiques enregistrées']);
    }
}