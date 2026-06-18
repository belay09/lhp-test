<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventAttendeeRequest;
use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EventAttendeeController extends Controller
{
    public function store(StoreEventAttendeeRequest $request, Event $event): RedirectResponse
    {
        if (in_array($event->status, ['sold_out', 'cancelled'], true)) {
            return back()->withErrors([
                'email' => 'Registration is closed for this event.',
            ]);
        }

        $attendee = $event->attendees()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'confirmed_at' => now(),
        ]);

        Mail::to($attendee->email)->queue(new EventRegistrationConfirmation($event, $attendee));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'You are registered for this event. Check your email for confirmation.',
        ]);

        return back();
    }
}
