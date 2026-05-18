<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Report;
use App\Notifications\ProfessorReminder;

class SendProfessorReminders extends Command
{
    protected $signature = 'app:send-professor-reminders';
    protected $description = 'Relance les professeurs pour les rapports en attente de notation';

    public function handle()
    {
        // On récupère tous les profs
        $professors = User::where('role', 'professeur')->get();
        $countSent = 0;

        foreach ($professors as $prof) {
            // Nombre de rapports soumis pour ses groupes
            $pendingCount = Report::where('status', 'soumis')
                ->whereHas('group.project', function($query) use ($prof) {
                    $query->where('superviseur_id', $prof->id);
                })->count();

            if ($pendingCount > 0) {
                $prof->notify(new ProfessorReminder($pendingCount));
                $countSent++;
            }
        }

        $this->info($countSent . ' professor reminders sent.');
    }
}

