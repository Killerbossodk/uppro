<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Message;
use App\Events\MessageSent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // Récupérer les messages d'un groupe
    public function index(Group $group)
    {
        $this->authorizeAccess($group);
        return response()->json(Message::where('group_id', $group->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get());
    }

    // Envoyer un message
    public function store(Request $request, Group $group)
{
    $this->authorizeAccess($group);
    $user = $request->user();

    $validated = $request->validate([
        'message' => 'required_without:attachment|nullable|string|max:1000',
        'attachment' => 'nullable|file|max:10240', // 10MB max
    ]);

    $attachmentUrl = null;
    $attachmentName = null;
    if ($request->hasFile('attachment')) {
        $file = $request->file('attachment');
        $path = $file->store('attachments', 'public');
        $attachmentUrl = Storage::url($path);
        $attachmentName = $file->getClientOriginalName();
    }

    $message = Message::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'message' => $validated['message'] ?? '',
        'attachment_url' => $attachmentUrl,
        'attachment_name' => $attachmentName,
        'lu' => false,
    ]);

    $message->load('user');
    
    broadcast(new MessageSent($message))->toOthers();

    // ✅ Notifier TOUS les membres du groupe (sauf l'expéditeur)
    $senderName = $user->prenom . ' ' . $user->name;
    
    foreach ($group->membres as $membre) {
        if ($membre->id !== $user->id) {
            Notification::create([
                'user_id' => $membre->id,
                'message' => "💬 Nouveau message de {$senderName} dans le groupe {$group->nom}",
                'type' => 'message',
                'canal' => 'app',
                'lu' => false,
                'data' => json_encode([
                    'group_id' => $group->id,
                    'sender' => $senderName,
                    'message_preview' => substr($validated['message'], 0, 100),
                ])
            ]);
        }
    }

    // ✅ Notifier le professeur encadrant
    $project = $group->project;
    if ($project && $project->superviseur_id) {
        $prof = \App\Models\User::find($project->superviseur_id);
        if ($prof && $prof->id !== $user->id) {
            Notification::create([
                'user_id' => $prof->id,
                'message' => "💬 Nouveau message de {$senderName} dans le groupe {$group->nom} (projet {$project->titre})",
                'type' => 'message',
                'canal' => 'app',
                'lu' => false,
                'data' => json_encode([
                    'group_id' => $group->id,
                    'sender' => $senderName,
                    'message_preview' => substr($validated['message'], 0, 100),
                ])
            ]);
        }
    }

    return response()->json($message, 201);
}

    // Marquer comme lu (optionnel)
    public function markAsRead(Request $request, Group $group)
    {
        $this->authorizeAccess($group);
        $user = $request->user();
        Message::where('group_id', $group->id)
            ->where('user_id', '!=', $user->id)
            ->update(['lu' => true]);
        return response()->json(['message' => 'Messages marqués comme lus']);
    }

    // Récupérer les messages privés avec un utilisateur
    public function indexPrivate(Request $request, User $receiver)
    {
        $user = $request->user();
        return response()->json(Message::where('is_private', true)
            ->where(function ($query) use ($user, $receiver) {
                $query->where(function ($q) use ($user, $receiver) {
                    $q->where('user_id', $user->id)->where('receiver_id', $receiver->id);
                })->orWhere(function ($q) use ($user, $receiver) {
                    $q->where('user_id', $receiver->id)->where('receiver_id', $user->id);
                });
            })
            ->with(['user', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get());
    }

    // Marquer les messages privés comme lus
    public function markPrivateAsRead(Request $request, User $sender)
    {
        $user = $request->user();
        Message::where('is_private', true)
            ->where('user_id', $sender->id)
            ->where('receiver_id', $user->id)
            ->update(['lu' => true]);
        return response()->json(['message' => 'Messages marqués comme lus']);
    }

    /**
     * Liste des contacts autorisés pour la messagerie privée
     */
    public function getContacts(Request $request)
    {
        $user = $request->user();
        $query = User::query()->where('id', '!=', $user->id);

        if ($user->role === 'etudiant') {
            // Un étudiant peut parler à :
            // 1. Son prof encadreur (superviseur du projet du groupe)
            // 2. Ses camarades du même groupe
            // 3. Son RUP Spécialité

            $group = Group::whereHas('membres', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('project')->first();

            $allowedIds = collect();

            if ($group) {
                // Membres du groupe
                $allowedIds = $allowedIds->merge($group->membres->pluck('id'));
                // Prof encadreur
                if ($group->project && $group->project->superviseur_id) {
                    $allowedIds->push($group->project->superviseur_id);
                }
                // RUP Spécialité
                if ($group->project && $group->project->specialite_id) {
                    $rup = User::where('role', 'rup_specialite')
                        ->where('specialite_id', $group->project->specialite_id)
                        ->pluck('id');
                    $allowedIds = $allowedIds->merge($rup);
                }
            } else {
                // Si l'étudiant n'a pas encore de groupe, il peut peut-être parler au RUP Projet ? 
                // Pour l'instant, on reste sur la demande.
                $rupProjet = User::where('role', 'rup_projet')->pluck('id');
                $allowedIds = $allowedIds->merge($rupProjet);
            }

            $query->whereIn('id', $allowedIds->unique());
        }

        // Les professeurs et RUP peuvent parler à tout le monde
        // (Ou on peut restreindre si besoin, mais la demande dit "un prof lui peux avec tout le monde")

        return response()->json($query->select('id', 'name', 'prenom', 'email', 'role')->get());
    }

    // Envoyer un message privé (version mise à jour avec vérification)
    public function storePrivate(Request $request, User $receiver)
    {
        $user = $request->user();

        // Restriction pour les étudiants
        if ($user->role === 'etudiant') {
            $allowedContacts = $this->getContacts($request)->getData(true);
            $isAllowed = collect($allowedContacts)->contains('id', $receiver->id);
            if (!$isAllowed) {
                return response()->json(['message' => 'Vous n\'êtes pas autorisé à envoyer un message privé à cet utilisateur.'], 403);
            }
        }

        $validated = $request->validate([
            'message' => 'required_without:attachment|nullable|string|max:1000',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentUrl = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            $attachmentUrl = Storage::url($path);
            $attachmentName = $file->getClientOriginalName();
        }

        $message = Message::create([
            'user_id' => $user->id,
            'receiver_id' => $receiver->id,
            'is_private' => true,
            'message' => $validated['message'] ?? '',
            'attachment_url' => $attachmentUrl,
            'attachment_name' => $attachmentName,
            'lu' => false,
        ]);

        $message->load(['user', 'receiver']);

        broadcast(new MessageSent($message))->toOthers();

        // Notifier le destinataire
        $senderName = $user->prenom . ' ' . $user->name;
        Notification::create([
            'user_id' => $receiver->id,
            'message' => "💬 Nouveau message privé de {$senderName}",
            'type' => 'message',
            'canal' => 'app',
            'lu' => false,
            'data' => json_encode([
                'sender_id' => $user->id,
                'sender_name' => $senderName,
                'message_preview' => substr($validated['message'] ?? 'Pièce jointe', 0, 100),
            ])
        ]);

        return response()->json($message, 201);
    }

    private function authorizeAccess($group)
    {
        $user = request()->user();
        $project = $group->project;
        if ($user->role === 'professeur' && $project->superviseur_id !== $user->id) {
            abort(403, 'Accès non autorisé');
        }
        if ($user->role === 'etudiant' && !$group->membres()->where('user_id', $user->id)->exists()) {
            abort(403, 'Accès non autorisé');
        }
        if ($user->role === 'rup_specialite' && $project->specialite_id !== $user->specialite_id) {
            abort(403, 'Accès non autorisé');
        }
        // rup_projet peut tout voir
    }
}