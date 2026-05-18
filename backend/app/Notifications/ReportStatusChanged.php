<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Report;

class ReportStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public $report;
    public $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report, $status)
    {
        $this->report = $report;
        $this->status = $status;
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
        $groupName = $this->report->group->nom ?? 'votre groupe';
        $statusText = $this->status === 'valide' ? 'validé' : 'rejeté';
        $typeLabel = $this->report->type === 'intermediaire' ? 'intermédiaire' : ($this->report->type === 'final' ? 'final' : $this->report->type);

        $mail = (new MailMessage)
            ->subject("UP-PRO : Votre rapport a été {$statusText}")
            ->greeting("Bonjour " . ($notifiable->prenom ?? $notifiable->name) . ",")
            ->line("Le rapport **{$typeLabel}** intitulé **\"{$this->report->titre}\"** pour le groupe {$groupName} a été examiné.");

        if ($this->status === 'rejete' || $this->status === 'rejeté') {
            $mail->line("Il a été **rejeté**. Veuillez consulter les commentaires de votre encadrant sur la plateforme et soumettre une nouvelle version corrigée.");
        } else {
            $mail->line("Il a été **validé** avec succès ! Félicitations pour votre travail.");
        }

        $mail->action('Voir le rapport sur UP-PRO', url('/etudiant/groupe'))
            ->line('Merci d\'utiliser UP-PRO !');
            
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'status' => $this->status,
            'message' => "Votre rapport a été " . ($this->status === 'valide' ? 'validé' : 'rejeté')
        ];
    }
}
