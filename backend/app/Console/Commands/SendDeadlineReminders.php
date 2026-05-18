<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Phase;
use App\Notifications\DeadlineReminder;
use Carbon\Carbon;

class SendDeadlineReminders extends Command
{
    protected $signature = 'app:send-deadline-reminders';
    protected $description = 'Envoie un rappel 3 jours avant la fin d\'une phase (deadline)';

    public function handle()
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $phases = Phase::whereDate('date_fin', $targetDate)
                       ->where('avancement_pct', '<', 100)
                       ->with('group.membres')
                       ->get();

        foreach ($phases as $phase) {
            if ($phase->group && $phase->group->membres) {
                foreach ($phase->group->membres as $membre) {
                    $membre->notify(new DeadlineReminder($phase));
                }
            }
        }

        $this->info(count($phases) . ' deadline reminders sent.');
    }
}

