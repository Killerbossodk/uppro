<?php

namespace App\Events;

use App\Models\Meeting;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoutenanceStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meetingId;
    public $statut;
    public $started_at;
    public $ended_at;

    /**
     * Create a new event instance.
     */
    public function __construct(Meeting $meeting)
    {
        $this->meetingId = $meeting->id;
        $this->statut = $meeting->statut_soutenance;
        $this->started_at = $meeting->started_at ? $meeting->started_at->toIsoString() : null;
        $this->ended_at = $meeting->ended_at ? $meeting->ended_at->toIsoString() : null;
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
        return 'soutenance.status_updated';
    }
}
