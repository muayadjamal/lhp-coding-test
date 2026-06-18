<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Mail\EventReminderMail;
use App\Models\Attendee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends attendance reminder emails as an event approaches: once when it is
 * within 3 days, and again when it is within 24 hours.
 *
 * Idempotent: per-attendee `reminder_*_sent_at` columns guard each reminder so
 * re-running (e.g. hourly via the scheduler) never double-sends.
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Email attendees a 3-day and 24-hour reminder before their event';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        // 24-hour reminder: event starts within the next 24 hours.
        $sent += $this->dispatch(
            column: 'reminder_24h_sent_at',
            fromTimestamp: $now->getTimestamp(),
            untilTimestamp: $now->copy()->addDay()->getTimestamp(),
            when: 'in less than 24 hours',
        );

        // 3-day reminder: event starts 1–3 days out. The lower bound excludes
        // the 24h window so an imminent event never gets both reminders at once.
        $sent += $this->dispatch(
            column: 'reminder_3d_sent_at',
            fromTimestamp: $now->copy()->addDay()->getTimestamp(),
            untilTimestamp: $now->copy()->addDays(3)->getTimestamp(),
            when: 'in 3 days',
        );

        $this->info("Queued {$sent} reminder email(s).");

        return self::SUCCESS;
    }

    private function dispatch(string $column, int $fromTimestamp, int $untilTimestamp, string $when): int
    {
        $count = 0;

        Attendee::query()
            ->whereNull($column)
            ->whereHas('event', function ($q) use ($fromTimestamp, $untilTimestamp) {
                $q->where('status', EventStatus::Published)
                    ->where('created_time', '>', $fromTimestamp)
                    ->where('created_time', '<=', $untilTimestamp);
            })
            ->with('event')
            ->chunkById(500, function ($attendees) use ($column, $when, &$count) {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->queue(new EventReminderMail($attendee, $when));
                    $attendee->forceFill([$column => now()])->save();
                    $count++;
                }
            });

        return $count;
    }
}
