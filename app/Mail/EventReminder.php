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

class EventReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  'three_days'|'twenty_four_hours'  $type
     */
    public function __construct(
        public Event $event,
        public EventAttendee $attendee,
        public string $type,
    ) {}

    public function envelope(): Envelope
    {
        $lead = $this->type === 'three_days' ? '3 days' : '24 hours';

        return new Envelope(
            subject: "Reminder: {$this->event->displayName()} starts in {$lead}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.event-reminder',
            with: [
                'eventName' => $this->event->displayName(),
                'attendeeName' => $this->attendee->name,
                'startsAt' => $this->event->startsAt(),
                'locationLabel' => $this->event->location_label,
                'reminderLead' => $this->type === 'three_days' ? '3 days' : '24 hours',
            ],
        );
    }
}
