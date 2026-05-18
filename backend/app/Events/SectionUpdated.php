<?php

namespace App\Events;

use App\Models\ReportSection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SectionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $section;

    public function __construct(ReportSection $section)
    {
        $this->section = $section->load('lockedBy');
    }

    public function broadcastOn()
    {
        return new Channel('report.' . $this->section->report_id);
    }

    public function broadcastAs()
    {
        return 'section.updated';
    }
}