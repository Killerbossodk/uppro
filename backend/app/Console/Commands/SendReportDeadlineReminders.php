<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReportDeadlineReminders extends Command
{
    protected $signature = 'reminders:reports';
    protected $description = 'Envoie des rappels pour les échéances de dépôt de rapports';

    public function handle()
    {
        $now = Carbon::now();

        $reports = Report::where('statut', '!=', 'soumis')
            ->whereNotNull('date_limite')
            ->get();

        foreach ($reports as $report) {
            // Vérification de type
            if (!$report instanceof Report) {
                $report = Report::find($report->id);
                if (!$report) continue;
            }

            $deadline = Carbon::parse($report->date_limite);
            $diffInDays = (int) $now->diffInDays($deadline, false);

            if (in_array($diffInDays, [3, 1, 0])) {
                $this->sendDeadlineReminderForReport($report, $diffInDays);
            }
        }

        $this->info('Rappels de rapports envoyés.');
        return Command::SUCCESS;
    }

    private function sendDeadlineReminderForReport(Report $report, int $daysLeft): void
    {
        $message = $daysLeft > 0
            ? "Rappel : il vous reste {$daysLeft} jour(s) pour déposer le rapport '{$report->titre}'."
            : "Rappel : la date limite pour déposer le rapport '{$report->titre}' est aujourd'hui !";

        $group = $report->group;
        if (!$group) return;

        $chef = $group->membres()->wherePivot('est_chef', true)->first();
        if ($chef) {
            Notification::create([
                'user_id' => $chef->id,
                'message' => $message,
                'type' => 'report_deadline',
                'canal' => 'app',
                'lu' => false,
                'data' => json_encode(['report_id' => $report->id])
            ]);
        }

        $prof = $group->project->superviseur ?? null;
        if ($prof) {
            Notification::create([
                'user_id' => $prof->id,
                'message' => "Échéance rapport '{$report->titre}' (groupe {$group->nom}) dans {$daysLeft} jour(s).",
                'type' => 'report_deadline',
                'canal' => 'app',
                'lu' => false,
                'data' => json_encode(['report_id' => $report->id])
            ]);
        }
    }
}