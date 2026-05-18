<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GradeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meetingId;
    public $groupId;
    public $note;
    public $commentaire;

    /**
     * Create a new event instance.
     */
    public function __construct($meetingId, $groupId, $note, $commentaire)
    {
        $this->meetingId = $meetingId;
        $this->groupId = $groupId;
        $this->note = $note;
        $this->commentaire = $commentaire;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('meeting.' . $this->meetingId),
        ];
    }

    public function broadcastAs()
    {
        return 'grade.updated';
    }
}
