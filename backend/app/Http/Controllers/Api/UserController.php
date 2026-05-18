<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Specialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs (filtrée selon le rôle connecté)
     */
    public function index(Request $request)
{
    $currentUser = $request->user();
    $query = User::query();

    // Filtre par rôle si demandé
    if ($request->has('role')) {
        $query->where('role', $request->role);
    }

    // 🔽 CORRECTION : pour un RUP Spécialité, on ne filtre PAS par spécialité
    // s'il demande explicitement la liste des professeurs (car il doit pouvoir
    // choisir parmi tous les professeurs pour constituer un jury).
    if ($currentUser->role === 'rup_specialite') {
        // Si on demande les professeurs, on ignore le filtre de spécialité
        if (!$request->has('role') || $request->role !== 'professeur') {
            $query->where('specialite_id', $currentUser->specialite_id);
        }
    }

    if ($currentUser->role === 'etudiant') {
        $query->where('role', 'professeur');
    }

    // Retourner tous les résultats (avec spécialité et niveau)
    $users = $query->with('specialite')->select('id', 'name', 'prenom', 'email', 'role', 'specialite_id', 'niveau')->get();

    return response()->json($users);
}
    /**
     * Créer un utilisateur (RUP Projet uniquement)
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();
        if (!in_array($currentUser->role, ['rup_projet'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'prenom' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:etudiant,professeur,rup_specialite,rup_projet',
            'specialite_id' => 'nullable|exists:specialites,id',
            'annee_universitaire' => 'nullable|string|max:9',
        ]);

        $plainPassword = $validated['password'];
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // 📧 Envoyer l'invitation si c'est un étudiant
        if ($user->role === 'etudiant') {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\StudentInvitation($user, $plainPassword));
        }

        return response()->json($user, 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $currentUser = $request->user();
        if (!in_array($currentUser->role, ['rup_projet'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'prenom' => 'nullable|string|max:100',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|in:etudiant,professeur,rup_specialite,rup_projet',
            'specialite_id' => 'nullable|exists:specialites,id',
            'annee_universitaire' => 'nullable|string|max:9',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        $currentUser = request()->user();
        if (!in_array($currentUser->role, ['rup_projet'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Empêcher de supprimer son propre compte ? Optionnel
        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    /**
     * Import CSV (simplifié)
     */
    public function import(Request $request)
    {
        $currentUser = $request->user();
        if (!in_array($currentUser->role, ['rup_projet'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        // Logique d'import (à implémenter selon besoins)
        // Retourner un résumé

        return response()->json(['message' => 'Import en cours de développement']);
    }
}