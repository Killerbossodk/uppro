<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GradePublished extends Notification implements ShouldQueue
{
    use Queueable;

    public $meeting;
    public $grade;

    /**
     * Create a new notification instance.
     */
    public function __construct($meeting, $grade)
    {
        $this->meeting = $meeting;
        $this->grade = $grade;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UP-PRO : Votre note a été publiée')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("La note pour votre soutenance / rendez-vous du **{$this->meeting->date}** a été publiée.")
            ->line("Note obtenue : **{$this->grade->valeur} / 20**")
            ->action('Voir les détails', url('/etudiant/groupe?id=' . $this->meeting->group_id))
            ->line('Continuez vos efforts sur votre projet !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'message' => "Votre note pour le rendez-vous du {$this->meeting->date} a été publiée."
        ];
    }
}
