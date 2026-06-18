<?php

namespace App\Mail;

use App\Models\Attendee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $when  Human label for the lead time, e.g. "in 3 days".
     */
    public function __construct(public Attendee $attendee, public string $when) {}

    public function envelope(): Envelope
    {
        $title = $this->attendee->event->title();

        return new Envelope(subject: "Reminder · {$title} is {$this->when}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event-reminder',
            with: [
                'event' => $this->attendee->event->toDisplayArray(),
                'attendee' => $this->attendee,
                'when' => $this->when,
            ],
        );
    }
}
