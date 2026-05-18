<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MeetingReminder extends Notification
{
    use Queueable;

    protected $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $date = $this->meeting->date_heure->format('d/m/Y H:i');
        return (new MailMessage)
            ->subject('Rappel de rendez-vous')
            ->line("Vous avez un RDV '{$this->meeting->titre}' le {$date}")
            ->action('Voir le RDV', url('/meetings/' . $this->meeting->id))
            ->line('Merci de votre attention.');
    }

    public function toArray($notifiable)
    {
        return [
            'meeting_id' => $this->meeting->id,
            'titre' => $this->meeting->titre,
            'date_heure' => $this->meeting->date_heure->toDateTimeString(),
        ];
    }
}