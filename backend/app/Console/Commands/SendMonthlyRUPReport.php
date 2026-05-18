<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Project;
use App\Models\Report;
use App\Notifications\MonthlyRUPReport;
use Carbon\Carbon;

class SendMonthlyRUPReport extends Command
{
    protected $signature = 'app:send-monthly-rup-report';
    protected $description = 'Envoie un bilan mensuel aux RUP';

    public function handle()
    {
        $rups = User::whereIn('role', ['rup_specialite', 'rup_projet'])->get();
        $lastMonth = Carbon::now()->subMonth();

        foreach ($rups as $rup) {
            $projectQuery = Project::query();
            $reportQuery = Report::query();

            if ($rup->role === 'rup_specialite' && $rup->specialite_id) {
                $projectQuery->where('specialite_id', $rup->specialite_id);
                $reportQuery->whereHas('group.project', function($q) use ($rup) {
                    $q->where('specialite_id', $rup->specialite_id);
                });
            }

            $stats = [
                'active_projects' => $projectQuery->where('est_archive', false)->count(),
                'validated_reports' => $reportQuery->where('status', 'valide')->where('updated_at', '>=', $lastMonth)->count(),
                'avg_grade' => 14.5, // Simplification pour le moment
            ];

            $rup->notify(new MonthlyRUPReport($stats));
        }

        $this->info(count($rups) . ' monthly RUP reports sent.');
    }
}

