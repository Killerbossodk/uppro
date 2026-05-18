<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        return response()->json($notifications);
    }
    
    public function markRead(Request $request, Notification $notification)
    {
        $user = $request->user();
        
        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $notification->update(['lu' => true]);
        
        return response()->json(['message' => 'Notification marquée comme lue']);
    }
    
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)
            ->where('lu', false)
            ->update(['lu' => true]);
        
        return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
    }
    public function destroy(Notification $notification)
{
    $user = request()->user();
    
    // Vérifier que la notification appartient bien à l'utilisateur
    if ($notification->user_id !== $user->id) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }
    
    $notification->delete();
    return response()->json(['message' => 'Notification supprimée']);
}
public function destroyAll(Request $request)
{
    $user = $request->user();
    
    // Supprimer toutes les notifications de type rup_report de l'utilisateur
    Notification::where('user_id', $user->id)
        ->where('type', 'rup_report')
        ->delete();
    
    return response()->json(['message' => 'Toutes les notifications de rapport supprimées']);
}
}