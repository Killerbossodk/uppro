<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EncadreurFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EncadreurFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        // Seul le RUP Projet, RUP Spécialité et les étudiants peuvent voir les critiques
        if (!in_array($user->role, ['rup_projet', 'rup_specialite', 'etudiant', 'admin'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $feedbacks = EncadreurFeedback::with(['encadreur', 'student'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Anonymisation des retours si anonyme est true
        $formatted = $feedbacks->map(function ($fb) {
            $studentInfo = null;
            if (!$fb->anonyme) {
                $studentInfo = [
                    'id' => $fb->student->id,
                    'prenom' => $fb->student->prenom,
                    'name' => $fb->student->name,
                ];
            } else {
                $studentInfo = [
                    'id' => null,
                    'prenom' => 'Étudiant',
                    'name' => 'Anonyme',
                ];
            }

            return [
                'id' => $fb->id,
                'note_pedagogique' => $fb->note_pedagogique,
                'commentaire' => $fb->commentaire,
                'anonyme' => $fb->anonyme,
                'created_at' => $fb->created_at,
                'student' => $studentInfo,
                'encadreur' => [
                    'id' => $fb->encadreur->id,
                    'prenom' => $fb->encadreur->prenom,
                    'name' => $fb->encadreur->name,
                ]
            ];
        });

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'etudiant') {
            return response()->json(['message' => 'Seul un étudiant peut évaluer un encadreur'], 403);
        }

        $validated = $request->validate([
            'encadreur_id' => 'required|exists:users,id',
            'note_pedagogique' => 'required|integer|min:1|max:5',
            'commentaire' => 'required|string|min:5|max:1000',
            'anonyme' => 'sometimes|boolean',
        ]);

        // Vérifier que la cible est bien un professeur / superviseur
        $target = User::find($validated['encadreur_id']);
        if (!in_array($target->role, ['professeur', 'rup_specialite', 'rup_projet'])) {
            return response()->json(['message' => 'L\'utilisateur sélectionné n\'est pas un encadreur'], 422);
        }

        $feedback = EncadreurFeedback::create([
            'student_id' => $user->id,
            'encadreur_id' => $validated['encadreur_id'],
            'note_pedagogique' => $validated['note_pedagogique'],
            'commentaire' => $validated['commentaire'],
            'anonyme' => $request->input('anonyme', true),
        ]);

        return response()->json([
            'message' => 'Critique enregistrée avec succès. Merci pour votre contribution !',
            'feedback' => $feedback
        ], 201);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['rup_projet', 'admin'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $stats = EncadreurFeedback::select('encadreur_id', DB::raw('AVG(note_pedagogique) as moyenne'), DB::raw('COUNT(*) as total_critiques'))
            ->groupBy('encadreur_id')
            ->with('encadreur')
            ->get();

        return response()->json($stats);
    }
}
