<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class FicheSoutenanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;
    public $jury;
    public $student;

    /**
     * Create a new message instance.
     */
    public function __construct($meeting, $jury, $student)
    {
        $this->meeting = $meeting;
        $this->jury = $jury;
        $this->student = $student;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Convocation : ' . $this->meeting->titre,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.fiche_soutenance',
            with: [
                'meeting' => $this->meeting,
                'jury' => $this->jury,
                'student' => $this->student,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        if ($this->meeting->piece_jointe) {
            $path = storage_path('app/public/' . str_replace('/storage/', '', $this->meeting->piece_jointe));
            if (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path);
            }
        }
        return $attachments;
    }
}
