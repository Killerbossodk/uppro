<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AcademicYearStarted extends Notification implements ShouldQueue
{
    use Queueable;

    public $academicYearLabel;
    public $startDate;

    /**
     * Create a new notification instance.
     */
    public function __construct($academicYearLabel, $startDate)
    {
        $this->academicYearLabel = $academicYearLabel;
        $this->startDate = $startDate;
    }

    /**
     * Get the notification's delivery channels.
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
        $formattedDate = date('d/m/Y', strtotime($this->startDate));

        $mailMessage = (new MailMessage)
            ->subject("UPPRO 🚀 Lancement de l'année universitaire {$this->academicYearLabel}")
            ->greeting("Bonjour " . ($notifiable->prenom ?: $notifiable->name) . ",")
            ->line("Nous avons le plaisir de vous annoncer le lancement officiel de la nouvelle année académique **{$this->academicYearLabel}** sur la plateforme UPPRO !")
            ->line("À partir du **{$formattedDate}**, l'ensemble des fonctionnalités d'accompagnement de vos projets et soutenances est actif.");

        if ($notifiable->role === 'etudiant') {
            $mailMessage->line("Vous pouvez dès à présent vous connecter pour former vos groupes de travail, soumettre vos thèmes de projet ou postuler aux sujets proposés par vos encadreurs.");
        } else {
            $mailMessage->line("Vous pouvez dès à présent vous connecter pour soumettre des sujets de projets, valider les groupes d'étudiants sous votre supervision et configurer vos rendez-vous de suivi.");
        }

        return $mailMessage
            ->action("Accéder à mon espace UPPRO", url('http://localhost:5173'))
            ->line("Toute l'équipe pédagogique d'UPPRO vous souhaite une excellente année universitaire pleine de réussite et d'innovation ! 🎓");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'academic_year_label' => $this->academicYearLabel,
            'message' => "L'année académique {$this->academicYearLabel} a démarré officiellement !"
        ];
    }
}
