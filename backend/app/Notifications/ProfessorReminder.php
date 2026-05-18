<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfessorReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public $pendingReportsCount;

    public function __construct($count)
    {
        $this->pendingReportsCount = $count;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UP-PRO : Rappel - Rapports en attente de notation')
            ->greeting("Bonjour Professeur {$notifiable->name},")
            ->line("Ceci est un rappel amical.")
            ->line("Vous avez actuellement **{$this->pendingReportsCount} rapport(s)** en attente de validation ou de notation.")
            ->action('Voir les rapports', url(config('app.frontend_url') . '/professeur/rapports'))
            ->line('Merci de votre implication dans le suivi des étudiants !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Vous avez {$this->pendingReportsCount} rapports en attente de notation."
        ];
    }
}

