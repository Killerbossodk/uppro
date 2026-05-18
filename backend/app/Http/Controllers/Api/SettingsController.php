<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $user = request()->user();
        
        return response()->json([
            'submissions_open' => Setting::get('submissions_open', '1') === '1',
            'inscriptions_open' => Setting::get('inscriptions_open', '1') === '1',
            'academic_year_start' => Setting::get('academic_year_start', ''),
            'academic_year_label' => Setting::get('academic_year_label', ''),
            'is_year_closed' => Setting::get('is_year_closed', '0') === '1',
        ]);
    }
    
    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $validated = $request->validate([
            'submissions_open' => 'boolean',
            'inscriptions_open' => 'boolean',
        ]);
        
        if (isset($validated['submissions_open'])) {
            Setting::set('submissions_open', $validated['submissions_open'] ? '1' : '0');
        }
        if (isset($validated['inscriptions_open'])) {
            Setting::set('inscriptions_open', $validated['inscriptions_open'] ? '1' : '0');
        }
        
        return response()->json(['message' => 'Paramètres mis à jour']);
    }

    public function startYear(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'academic_year_start' => 'required|date',
            'academic_year_label' => 'required|string|max:9',
        ]);

        Setting::set('academic_year_start', $validated['academic_year_start']);
        Setting::set('academic_year_label', $validated['academic_year_label']);
        Setting::set('is_year_closed', '0');

        // Envoyer la notification email & base de données à tous les étudiants et professeurs/RUPs
        try {
            $users = \App\Models\User::whereIn('role', ['etudiant', 'professeur', 'rup_specialite'])->get();
            foreach ($users as $u) {
                $u->notify(new \App\Notifications\AcademicYearStarted($validated['academic_year_label'], $validated['academic_year_start']));
            }
        } catch (\Exception $e) {
            // Ignorer silencieusement d'éventuelles erreurs SMTP locales pour ne pas bloquer le démarrage de l'année
            \Log::warning("Erreur envoi notification début d'année: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Nouvelle année académique commencée avec succès !',
            'academic_year_start' => $validated['academic_year_start'],
            'academic_year_label' => $validated['academic_year_label'],
            'is_year_closed' => false
        ]);
    }

    public function closeYear(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'rup_projet') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Mettre à jour le statut dans la table settings
        Setting::set('is_year_closed', '1');

        // Archiver tous les projets en cours (est_archive = true)
        \App\Models\Project::where('est_archive', false)->update(['est_archive' => true]);

        return response()->json([
            'message' => 'Année académique clôturée avec succès. Tous les projets et rapports ont été transférés dans les archives historiques.',
            'is_year_closed' => true
        ]);
    }
}