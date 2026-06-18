<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public EventAttendee $attendee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're registered for {$this->event->displayName()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.event-registration',
            with: [
                'eventName' => $this->event->displayName(),
                'attendeeName' => $this->attendee->name,
                'startsAt' => $this->event->startsAt(),
                'locationLabel' => $this->event->location_label,
            ],
        );
    }
}
