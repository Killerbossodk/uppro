<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Project;
use App\Models\Meeting;
use App\Models\Report;
use App\Notifications\WeeklyNewsletter;
use Carbon\Carbon;

class SendWeeklyNewsletter extends Command
{
    protected $signature = 'app:send-weekly-newsletter';
    protected $description = 'Envoie la newsletter hebdomadaire tous les lundis';

    public function handle()
    {
        $lastWeek = Carbon::now()->subWeek();

        $stats = [
            'new_projects' => Project::where('created_at', '>=', $lastWeek)->count(),
            'upcoming_meetings' => Meeting::where('date_heure', '>=', Carbon::now())
                                          ->where('date_heure', '<=', Carbon::now()->addWeek())
                                          ->count(),
            'pending_reports' => Report::where('status', 'soumis')->count(),
        ];

        // On peut l\'envoyer à tous les professeurs et RUPs, ou à tout le monde.
        // Disons à tout le monde pour l\'instant.
        $users = User::all();

        foreach ($users as $user) {
            $user->notify(new WeeklyNewsletter($stats));
        }

        $this->info('Weekly newsletter sent successfully.');
    }
}

