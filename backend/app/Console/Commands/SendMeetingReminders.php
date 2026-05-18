<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMeetingReminders extends Command
{
    protected $signature = 'reminders:meetings';
    protected $description = 'Envoie des rappels pour les RDV à venir (24h et 1h avant)';

    public function handle()
    {
        $now = Carbon::now();

        // Récupérer les meetings dans les plages horaires
        $meetings24h = $this->getMeetingsInRange(
            $now->copy()->addHours(24)->subMinutes(5),
            $now->copy()->addHours(24)->addMinutes(5)
        );

        foreach ($meetings24h as $meeting) {
            $this->sendReminderForMeeting($meeting, '24h');
        }

        $meetings1h = $this->getMeetingsInRange(
            $now->copy()->addHour()->subMinutes(5),
            $now->copy()->addHour()->addMinutes(5)
        );

        foreach ($meetings1h as $meeting) {
            $this->sendReminderForMeeting($meeting, '1h');
        }

        $this->info('Rappels de RDV envoyés.');
        return Command::SUCCESS;
    }

    /**
     * Récupère les meetings dans un intervalle de dates.
     * Garantit que chaque élément retourné est une instance de Meeting.
     */
    private function getMeetingsInRange(Carbon $start, Carbon $end)
    {
        // Utiliser le Query Builder du modèle pour garantir le typage
        $meetings = Meeting::whereBetween('date_heure', [$start, $end])->get();

        // Vérification supplémentaire : si des éléments sont des stdClass, les hydrater en modèle
        return $meetings->map(function ($item) {
            if ($item instanceof Meeting) {
                return $item;
            }
            // Si c'est un stdClass, on le convertit en modèle (peu probable mais par sécurité)
            return Meeting::find($item->id);
        })->filter(); // Supprime les null
    }

    private function sendReminderForMeeting(Meeting $meeting, string $delay): void
    {
        $message = $delay === '24h'
            ? "Rappel : RDV '{$meeting->titre}' dans 24 heures."
            : "Rappel : RDV '{$meeting->titre}' dans 1 heure.";

        // Notification pour l'organisateur (App + Email)
        $orgAppNotif = Notification::create([
            'user_id' => $meeting->organisateur_id,
            'message' => $message,
            'type' => 'rdv_reminder',
            'canal' => 'app',
            'lu' => false,
            'data' => json_encode(['meeting_id' => $meeting->id])
        ]);
        if ($meeting->organisateur) {
            $meeting->organisateur->notify(new \App\Notifications\MeetingReminder($meeting));
        }

        // Notifier les étudiants des groupes invités (App + Email)
        foreach ($meeting->groupes as $group) {
            foreach ($group->membres as $etudiant) {
                Notification::create([
                    'user_id' => $etudiant->id,
                    'message' => $message,
                    'type' => 'rdv_reminder',
                    'canal' => 'app',
                    'lu' => false,
                    'data' => json_encode(['meeting_id' => $meeting->id])
                ]);
                $etudiant->notify(new \App\Notifications\MeetingReminder($meeting));
            }
        }
    }
}