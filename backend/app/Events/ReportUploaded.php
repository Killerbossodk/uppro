<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.' . $this->report->group_id),
        ];
    }

    public function broadcastAs()
    {
        return 'report.uploaded';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->report->id,
            'titre' => $this->report->titre,
            'version' => $this->report->version,
            'type' => $this->report->type,
            'deposant' => $this->report->deposant->name,
            'created_at' => $this->report->created_at->toDateTimeString(),
        ];
    }
}