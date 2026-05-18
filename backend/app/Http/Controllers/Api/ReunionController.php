<?php
// app/Http/Controllers/Api/ReunionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reunion;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReunionController extends Controller
{
    /**
     * Liste des réunions (selon rôle)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Reunion::with('organisateur', 'participants')
            ->orderBy('date_heure', 'asc');
        
        // Filtrer selon le rôle
        if ($user->role === 'professeur') {
            $query->whereHas('participants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'rup_specialite') {
            // Le RUP spécialité voit ses réunions et celles qu'il a créées
            $query->where(function($q) use ($user) {
                $q->where('organisateur_id', $user->id)
                  ->orWhereHas('participants', fn($sq) => $sq->where('user_id', $user->id));
            });
        }
        
        return response()->json($query->get());
    }
    
    /**
     * Créer une réunion (RUP Spécialité uniquement)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'rup_specialite') {
            return response()->json(['message' => 'Seul le RUP Spécialité peut créer une réunion'], 403);
        }
        
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'date_heure' => 'required|date',
            'lieu' => 'nullable|string|max:255',
            'lien_visio' => 'nullable|url|max:500',
            'participants_ids' => 'required|array|min:1',
            'participants_ids.*' => 'exists:users,id,role,professeur'
        ]);
        
        DB::beginTransaction();
        
        try {
            $reunion = Reunion::create([
                'titre' => $validated['titre'],
                'description' => $validated['description'],
                'date_heure' => $validated['date_heure'],
                'lieu' => $validated['lieu'] ?? null,
                'lien_visio' => $validated['lien_visio'] ?? null,
                'organisateur_id' => $user->id,
            ]);
            
            // Ajouter les participants
            $reunion->participants()->attach($validated['participants_ids'], ['statut' => 'invite']);
            
            // Notifier chaque participant
            foreach ($validated['participants_ids'] as $profId) {
                Notification::create([
                    'user_id' => $profId,
                    'message' => "📅 Réunion planifiée : {$validated['titre']} le " . 
                                 \Carbon\Carbon::parse($validated['date_heure'])->format('d/m/Y H:i'),
                    'type' => 'rdv',
                    'canal' => 'app',
                    'lu' => false,
                    'data' => json_encode([
                        'reunion_id' => $reunion->id,
                        'type' => 'reunion'
                    ])
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Réunion créée avec succès',
                'reunion' => $reunion->load('organisateur', 'participants')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Afficher une réunion
     */
    public function show(Reunion $reunion)
    {
        $user = request()->user();
        
        $isParticipant = $reunion->participants()->where('user_id', $user->id)->exists();
        $isOrganisateur = $reunion->organisateur_id === $user->id;
        
        if (!$isParticipant && !$isOrganisateur && $user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        return response()->json($reunion->load('organisateur', 'participants'));
    }
    
    /**
     * Répondre à une invitation (professeur)
     */
    public function respond(Request $request, Reunion $reunion)
    {
        $user = $request->user();
        
        if ($user->role !== 'professeur') {
            return response()->json(['message' => 'Seul un professeur peut répondre'], 403);
        }
        
        $participant = $reunion->participants()->where('user_id', $user->id)->first();
        if (!$participant) {
            return response()->json(['message' => 'Vous n\'êtes pas invité à cette réunion'], 403);
        }
        
        $validated = $request->validate([
            'statut' => 'required|in:accepte,refuse'
        ]);
        
        $reunion->participants()->updateExistingPivot($user->id, ['statut' => $validated['statut']]);
        
        // Notifier l'organisateur
        Notification::create([
            'user_id' => $reunion->organisateur_id,
            'message' => "👨‍🏫 {$user->name} {$user->prenom} a {$validated['statut']} l'invitation pour '{$reunion->titre}'",
            'type' => 'rdv',
            'canal' => 'app',
            'lu' => false,
            'data' => json_encode(['reunion_id' => $reunion->id])
        ]);
        
        return response()->json(['message' => 'Réponse enregistrée']);
    }
    
    /**
     * Supprimer une réunion (RUP Spécialité uniquement)
     */
    public function destroy(Reunion $reunion)
    {
        $user = request()->user();
        
        if ($user->role !== 'rup_specialite' || $reunion->organisateur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        // Notifier les participants de l'annulation
        foreach ($reunion->participants as $participant) {
            Notification::create([
                'user_id' => $participant->id,
                'message' => "❌ Réunion annulée : {$reunion->titre}",
                'type' => 'rdv',
                'canal' => 'app',
                'lu' => false,
                'data' => json_encode(['reunion_id' => $reunion->id])
            ]);
        }
        
        $reunion->delete();
        
        return response()->json(['message' => 'Réunion annulée']);
    }
}