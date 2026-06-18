<?php

namespace App\Mail;

use App\Models\Attendee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendeeConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Attendee $attendee) {}

    public function envelope(): Envelope
    {
        $title = $this->attendee->event->title();

        return new Envelope(subject: "You're on the list · {$title}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.attendee-confirmation',
            with: ['event' => $this->attendee->event->toDisplayArray(), 'attendee' => $this->attendee],
        );
    }
}
