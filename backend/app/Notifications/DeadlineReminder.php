<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Phase;

class DeadlineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public $phase;

    public function __construct(Phase $phase)
    {
        $this->phase = $phase;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UP-PRO : Rappel de délai (J-3)')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Ceci est un rappel automatique : la phase **{$this->phase->titre}** arrive à échéance dans 3 jours.")
            ->line("Date limite : **" . \Carbon\Carbon::parse($this->phase->date_fin)->format('d/m/Y') . "**")
            ->action('Voir mon groupe', url(config('app.frontend_url') . '/etudiant/groupe?id=' . $this->phase->group_id))
            ->line('Pensez à soumettre votre travail à temps !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'phase_id' => $this->phase->id,
            'message' => "La phase {$this->phase->titre} se termine dans 3 jours."
        ];
    }
}

