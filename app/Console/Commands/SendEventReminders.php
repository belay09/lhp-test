<?php

namespace App\Console\Commands;

use App\Mail\EventReminder;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send 3-day and 24-hour reminder emails to event attendees';

    public function handle(): int
    {
        $this->sendReminders('three_days', days: 3, stampColumn: 'reminded_3d_at');
        $this->sendReminders('twenty_four_hours', days: 1, stampColumn: 'reminded_24h_at');

        return self::SUCCESS;
    }

    /**
     * @param  'three_days'|'twenty_four_hours'  $type
     */
    private function sendReminders(string $type, int $days, string $stampColumn): void
    {
        [$windowStart, $windowEnd] = $this->reminderWindow($days);

        $events = Event::query()
            ->whereBetween('created_time', [$windowStart, $windowEnd])
            ->get();

        foreach ($events as $event) {
            $attendees = $event->attendees()
                ->whereNull($stampColumn)
                ->get();

            foreach ($attendees as $attendee) {
                Mail::to($attendee->email)->queue(new EventReminder($event, $attendee, $type));
                $attendee->update([$stampColumn => now()]);
            }

            if ($attendees->isNotEmpty()) {
                $this->info("Queued {$attendees->count()} {$type} reminder(s) for {$event->displayName()}.");
            }
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function reminderWindow(int $days): array
    {
        $target = Carbon::now('UTC')->addDays($days);

        return [
            (int) $target->copy()->subHour()->timestamp,
            (int) $target->copy()->addHour()->timestamp,
        ];
    }
}
