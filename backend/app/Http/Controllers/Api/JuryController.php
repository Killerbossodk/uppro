<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Jury;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JuryController extends Controller
{
    /**
     * Lister les jurys
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Jury::with('meeting.project', 'president', 'membres');

        if (in_array($user->role, ['rup_specialite', 'rup_projet', 'rup'])) {
            $query->whereHas('meeting.project', function ($q) use ($user) {
                if ($user->role === 'rup_specialite') {
                    $q->where('specialite_id', $user->specialite_id)
                      ->orWhereNull('specialite_id');
                }
                // rup_projet can see all or maybe filtered by their project scope if any
            });
        } elseif ($user->role === 'professeur') {
            $query->where(function ($q) use ($user) {
                $q->where('president_id', $user->id)
                  ->orWhereHas('membres', fn($sq) => $sq->where('user_id', $user->id));
            });
        } elseif ($user->role === 'etudiant') {
            $query->whereHas('meeting.groupes', function ($q) use ($user) {
                $q->whereHas('membres', fn($sq) => $sq->where('user_id', $user->id));
            });
        }

        return response()->json($query->get());
    }

    /**
     * Créer un jury
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['rup_projet', 'rup'])) {
            return response()->json(['message' => 'Seul le RUP Projet peut créer un jury'], 403);
        }

        try {
            $validated = $request->validate([
                'meeting_id' => 'required|exists:meetings,id',
                'president_id' => 'required|exists:users,id',
                'salle' => 'required|string|max:100',
                'date_heure' => 'required|date',
                'membres_ids' => 'nullable|array',
            ]);

            $meeting = Meeting::with('project')->find($validated['meeting_id']);
            
            if (!$meeting) {
                return response()->json(['message' => 'Soutenance introuvable'], 404);
            }
            
            if ($meeting->type !== 'soutenance') {
                return response()->json(['message' => 'Cette réunion n\'est pas une soutenance'], 422);
            }

            // Vérifier qu'il n'y a pas déjà un jury
            $existing = Jury::where('meeting_id', $validated['meeting_id'])->first();
            if ($existing) {
                return response()->json(['message' => 'Un jury existe déjà pour cette soutenance'], 422);
            }

            DB::beginTransaction();
            
            // Créer le jury
            $jury = Jury::create([
                'meeting_id' => $validated['meeting_id'],
                'president_id' => $validated['president_id'],
                'salle' => $validated['salle'],
                'date_heure' => $validated['date_heure'],
            ]);

            // Ajouter le président comme membre
            $jury->membres()->attach($validated['president_id'], ['role_jury' => 'president']);

            // Ajouter les examinateurs
            if (!empty($validated['membres_ids'])) {
                foreach ($validated['membres_ids'] as $membreId) {
                    if ($membreId != $validated['president_id']) {
                        $jury->membres()->attach($membreId, ['role_jury' => 'examinateur']);
                    }
                }
            }

            // Notification pour le président - ✅ TYPE CORRIGÉ
            Notification::create([
                'user_id' => $validated['president_id'],
                'message' => "Vous êtes président du jury pour la soutenance : {$meeting->titre}",
                'type' => 'rdv',
                'canal' => 'app',
                'lu' => false,
                'data' => json_encode(['meeting_id' => $meeting->id])
            ]);

            // Notifications pour les examinateurs
            if (!empty($validated['membres_ids'])) {
                foreach ($validated['membres_ids'] as $membreId) {
                    if ($membreId != $validated['president_id']) {
                        Notification::create([
                            'user_id' => $membreId,
                            'message' => "Vous êtes examinateur pour la soutenance : {$meeting->titre}",
                            'type' => 'rdv',
                            'canal' => 'app',
                            'lu' => false,
                            'data' => json_encode(['meeting_id' => $meeting->id])
                        ]);
                    }
                }
            }

            // Notification et Email pour les étudiants
            foreach ($meeting->groupes as $group) {
                foreach ($group->membres as $membre) {
                    Notification::create([
                        'user_id' => $membre->id,
                        'message' => "Votre jury a été constitué. Consultez votre convocation.",
                        'type' => 'rdv',
                        'canal' => 'app',
                        'lu' => false,
                        'data' => json_encode(['meeting_id' => $meeting->id])
                    ]);
                    
                    \Illuminate\Support\Facades\Mail::to($membre->email)->send(new \App\Mail\FicheSoutenanceMail($meeting, $jury, $membre));
                }
            }

            DB::commit();
            
            $jury->load('meeting', 'president', 'membres');
            
            return response()->json([
                'message' => 'Jury créé avec succès',
                'jury' => $jury
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création jury: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Erreur lors de la création du jury',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un jury
     */
    public function show(Jury $jury)
    {
        return response()->json($jury->load('meeting.project', 'president', 'membres'));
    }

    /**
     * Modifier un jury
     */
    public function update(Request $request, Jury $jury)
    {
        $user = $request->user();
        if (!in_array($user->role, ['rup_specialite', 'rup_projet', 'rup'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            $validated = $request->validate([
                'salle' => 'sometimes|string|max:100',
                'date_heure' => 'sometimes|date',
                'president_id' => 'sometimes|exists:users,id',
                'membres_ids' => 'nullable|array',
            ]);

            DB::beginTransaction();

            if (isset($validated['salle'])) {
                $jury->salle = $validated['salle'];
            }
            if (isset($validated['date_heure'])) {
                $jury->date_heure = $validated['date_heure'];
            }
            if (isset($validated['president_id'])) {
                $jury->president_id = $validated['president_id'];
            }
            $jury->save();

            if (isset($validated['membres_ids'])) {
                $syncData = [];
                $presidentId = $validated['president_id'] ?? $jury->president_id;
                $syncData[$presidentId] = ['role_jury' => 'president'];
                
                foreach ($validated['membres_ids'] as $membreId) {
                    if ($membreId != $presidentId) {
                        $syncData[$membreId] = ['role_jury' => 'examinateur'];
                    }
                }
                
                $jury->membres()->sync($syncData);
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Jury modifié avec succès',
                'jury' => $jury->load('membres', 'president')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur modification jury: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Erreur lors de la modification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un jury
     */
    public function destroy(Jury $jury)
    {
        $user = request()->user();
        if (!in_array($user->role, ['rup_specialite', 'rup'])) {
            return response()->json(['message' => 'Non autorisé. Le RUP Projet ne peut pas annuler un jury.'], 403);
        }

        try {
            $jury->delete();
            return response()->json(['message' => 'Jury supprimé']);
        } catch (\Exception $e) {
            Log::error('Erreur suppression jury: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer le jury d'une soutenance
     */
    public function showByMeeting(Meeting $meeting)
    {
        $jury = Jury::where('meeting_id', $meeting->id)
            ->with('president', 'membres')
            ->first();
        
        if (!$jury) {
            return response()->json(null);
        }
        
        return response()->json($jury);
    }

    /**
     * Notifier les membres
     */
    public function notify(Jury $jury)
    {
        $user = request()->user();
        if (!in_array($user->role, ['rup_specialite', 'rup_projet', 'rup'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            $meeting = $jury->meeting;
            
            Notification::create([
                'user_id' => $jury->president_id,
                'message' => "Rappel : Vous êtes président du jury pour {$meeting->titre}",
                'type' => 'rdv',
                'canal' => 'app',
                'lu' => false,
            ]);
            
            foreach ($jury->membres as $membre) {
                if ($membre->id != $jury->president_id) {
                    Notification::create([
                        'user_id' => $membre->id,
                        'message' => "Rappel : Vous êtes examinateur pour {$meeting->titre}",
                        'type' => 'rdv',
                        'canal' => 'app',
                        'lu' => false,
                    ]);
                }
            }
            
            return response()->json(['message' => 'Notifications envoyées']);
            
        } catch (\Exception $e) {
            Log::error('Erreur notification jury: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de l\'envoi des notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}