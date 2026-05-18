<?php

namespace App\Events;

use App\Models\Meeting;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CritiquesUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $meetingId;
    public $critiques;

    /**
     * Create a new event instance.
     */
    public function __construct(Meeting $meeting)
    {
        $this->meetingId = $meeting->id;
        $this->critiques = $meeting->critiques_generales;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('meeting.' . $this->meetingId),
        ];
    }

    public function broadcastAs()
    {
        return 'critiques.updated';
    }
}
