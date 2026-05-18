<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyNewsletter extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UP-PRO : Votre résumé hebdomadaire')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Voici un aperçu de l'activité sur UP-PRO pour cette semaine :")
            ->line("- Nouveaux projets déposés : **{$this->data['new_projects']}**")
            ->line("- Rendez-vous à venir : **{$this->data['upcoming_meetings']}**")
            ->line("- Rapports en attente : **{$this->data['pending_reports']}**")
            ->action('Accéder à la plateforme', url(config('app.frontend_url')))
            ->line('Bonne semaine !');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}

