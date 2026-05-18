<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyRUPReport extends Notification implements ShouldQueue
{
    use Queueable;

    public $stats;

    public function __construct($stats)
    {
        $this->stats = $stats;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UP-PRO : Bilan Mensuel de votre Spécialité')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Veuillez trouver ci-dessous le bilan mensuel pour votre spécialité :")
            ->line("- Projets en cours : **{$this->stats['active_projects']}**")
            ->line("- Rapports validés ce mois : **{$this->stats['validated_reports']}**")
            ->line("- Note moyenne estimée : **{$this->stats['avg_grade']} / 20**")
            ->action('Voir les détails sur UP-PRO', url(config('app.frontend_url') . '/rup/dashboard'))
            ->line('Merci pour votre supervision globale.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}

