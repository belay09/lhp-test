<?php

use App\Mail\EventRegistrationConfirmation;
use App\Mail\EventReminder;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createPublishedEvent(array $attributes = []): Event
{
    $user = User::factory()->create();

    return Event::factory()->for($user)->create(array_merge([
        'status' => 'published',
        'created_time' => now()->addWeek()->timestamp,
    ], $attributes));
}

it('registers an attendee for a published event', function () {
    Mail::fake();

    $event = createPublishedEvent();

    $this->from(route('events.show', $event))
        ->post(route('events.attendees.store', $event), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])
        ->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('event_attendees', [
        'event_id' => $event->id,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    Mail::assertQueued(EventRegistrationConfirmation::class, function (EventRegistrationConfirmation $mail) use ($event) {
        return $mail->event->is($event) && $mail->attendee->email === 'ada@example.com';
    });
});

it('rejects duplicate attendee emails for the same event', function () {
    Mail::fake();

    $event = createPublishedEvent();
    EventAttendee::factory()->for($event)->create(['email' => 'ada@example.com']);

    $this->from(route('events.show', $event))
        ->post(route('events.attendees.store', $event), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect($event->attendees()->count())->toBe(1);
    Mail::assertNothingQueued();
});

it('rejects registration for sold out and cancelled events', function (string $status) {
    Mail::fake();

    $event = createPublishedEvent(['status' => $status]);

    $this->from(route('events.show', $event))
        ->post(route('events.attendees.store', $event), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect($event->attendees()->count())->toBe(0);
    Mail::assertNothingQueued();
})->with(['sold_out', 'cancelled']);

it('shows attendees on the event detail page', function () {
    $event = createPublishedEvent();
    EventAttendee::factory()->for($event)->create(['name' => 'Grace Hopper']);
    EventAttendee::factory()->for($event)->create(['name' => 'Alan Turing']);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->has('attendees.data', 2)
            ->where('attendees.total', 2)
            ->where('attendees.data', fn ($attendees) => collect($attendees)
                ->pluck('name')
                ->sort()
                ->values()
                ->all() === ['Alan Turing', 'Grace Hopper']
            )
        );
});

it('sends three day reminders for events starting in three days', function () {
    Mail::fake();
    Carbon::setTestNow('2024-06-01 12:00:00 UTC');

    $event = createPublishedEvent([
        'created_time' => Carbon::parse('2024-06-04 12:30:00', 'UTC')->timestamp,
    ]);
    $attendee = EventAttendee::factory()->for($event)->create();

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, function (EventReminder $mail) use ($attendee) {
        return $mail->attendee->is($attendee) && $mail->type === 'three_days';
    });

    expect($attendee->fresh()->reminded_3d_at)->not->toBeNull();
    expect($attendee->fresh()->reminded_24h_at)->toBeNull();
});

it('sends twenty four hour reminders for events starting in one day', function () {
    Mail::fake();
    Carbon::setTestNow('2024-06-01 12:00:00 UTC');

    $event = createPublishedEvent([
        'created_time' => Carbon::parse('2024-06-02 12:00:00', 'UTC')->timestamp,
    ]);
    $attendee = EventAttendee::factory()->for($event)->create();

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, function (EventReminder $mail) use ($attendee) {
        return $mail->attendee->is($attendee) && $mail->type === 'twenty_four_hours';
    });

    expect($attendee->fresh()->reminded_24h_at)->not->toBeNull();
    expect($attendee->fresh()->reminded_3d_at)->toBeNull();
});

it('does not send duplicate reminder emails', function () {
    Mail::fake();
    Carbon::setTestNow('2024-06-01 12:00:00 UTC');

    $event = createPublishedEvent([
        'created_time' => Carbon::parse('2024-06-04 12:00:00', 'UTC')->timestamp,
    ]);
    EventAttendee::factory()->for($event)->create([
        'reminded_3d_at' => now(),
    ]);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('schedules the reminder command hourly', function () {
    $this->artisan('schedule:list')
        ->assertSuccessful()
        ->expectsOutputToContain('events:send-reminders');
});
