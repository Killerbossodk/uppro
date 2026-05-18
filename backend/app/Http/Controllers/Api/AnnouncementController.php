<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // Méthode privée pour éviter la duplication de code
    private function getFilteredQuery(Request $request)
    {
        $user = $request->user();
        $query = Announcement::with('creator')->orderBy('created_at', 'desc');

        if ($user->role !== 'rup_projet') {
            $query->where(function ($q) use ($user) {
                $q->where('target_type', 'all');
                
                if ($user->role === 'professeur') {
                    $q->orWhere('target_type', 'professors');
                } elseif ($user->role === 'etudiant') {
                    $q->orWhere('target_type', 'students');
                    
                    $userGroups = $user->groupesMembres->pluck('id');
                    if ($userGroups->isNotEmpty()) {
                        $q->orWhere(function ($sub) use ($userGroups) {
                            $sub->where('target_type', 'group')
                                ->whereIn('target_id', $userGroups);
                        });
                    }
                }
                
                $q->orWhere(function ($sub) use ($user) {
                    $sub->where('target_type', 'user')
                        ->where('target_id', $user->id);
                });
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        return response()->json($this->getFilteredQuery($request)->paginate(10));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_type' => 'required|in:all,professors,students,group,user',
            'target_id' => 'nullable|integer'
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'created_by' => $user->id
        ]);

        return response()->json($announcement, 201);
    }

    public function destroy(Announcement $announcement)
    {
        $user = request()->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $announcement->delete();
        return response()->json(['message' => 'Annonce supprimée']);
    }

    public function latest(Request $request)
    {
        $announcement = $this->getFilteredQuery($request)->first();
        return response()->json($announcement);
    }

    public function carousel(Request $request)
    {
        // 5 dernières annonces filtrées par rôle
        $announcements = $this->getFilteredQuery($request)->limit(5)->get();
        return response()->json($announcements);
    }
}