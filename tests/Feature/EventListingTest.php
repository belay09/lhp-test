<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the events listing shell without authentication', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('statuses', 4)
            ->where('filters.from', '2023-01-01')
        );
});

it('returns a json page of events with load stats for lazy loading', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'location_label' => 'New York, US',
    ]);

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.created_time', 1_700_000_000)
        ->assertJsonPath('data.0.latitude', 40.7128)
        ->assertJsonPath('data.0.location_label', 'New York, US')
        ->assertJsonPath('data.0.user.name', 'Ada Lovelace');
});

it('filters the data endpoint by status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'published']);
    Event::factory()->for($user)->create(['status' => 'cancelled']);

    $this->getJson(route('events.data', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'cancelled');
});

it('filters the data endpoint by from date on created_time', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2024-06-15 12:00:00 UTC'),
    ]);
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2023-06-15 12:00:00 UTC'),
    ]);

    $this->getJson(route('events.data', ['from' => '2024-01-01']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.created_time', strtotime('2024-06-15 12:00:00 UTC'));
});

it('filters the data endpoint by to date on created_time', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2023-06-15 12:00:00 UTC'),
    ]);
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2024-06-15 12:00:00 UTC'),
    ]);

    $this->getJson(route('events.data', ['to' => '2023-12-31']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.created_time', strtotime('2023-06-15 12:00:00 UTC'));
});

it('filters the data endpoint by date range on created_time', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2024-03-01 00:00:00 UTC'),
    ]);
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2024-06-01 00:00:00 UTC'),
    ]);
    Event::factory()->for($user)->create([
        'created_time' => strtotime('2024-09-01 00:00:00 UTC'),
    ]);

    $this->getJson(route('events.data', ['from' => '2024-02-01', 'to' => '2024-07-01']))
        ->assertOk()
        ->assertJsonPath('total', 2);
});

it('filters the data endpoint by location label', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['location_label' => 'London, UK']);
    Event::factory()->for($user)->create(['location_label' => 'New York, US']);

    $this->getJson(route('events.data', ['location' => 'London']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.location_label', 'London, UK');
});

it('combines status, date, and location filters', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => strtotime('2024-05-10 10:00:00 UTC'),
        'location_label' => 'Paris, FR',
    ]);
    Event::factory()->for($user)->create([
        'status' => 'draft',
        'created_time' => strtotime('2024-05-10 10:00:00 UTC'),
        'location_label' => 'Paris, FR',
    ]);
    Event::factory()->for($user)->create([
        'status' => 'published',
        'created_time' => strtotime('2023-05-10 10:00:00 UTC'),
        'location_label' => 'Paris, FR',
    ]);

    $this->getJson(route('events.data', [
        'status' => 'published',
        'from' => '2024-01-01',
        'location' => 'Paris',
    ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'published')
        ->assertJsonPath('data.0.location_label', 'Paris, FR');
});

it('paginates filtered results at fifty per page', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->count(55)->create([
        'status' => 'published',
        'created_time' => strtotime('2024-05-10 10:00:00 UTC'),
    ]);

    $this->getJson(route('events.data', [
        'status' => 'published',
        'from' => '2024-01-01',
    ]))
        ->assertOk()
        ->assertJsonPath('total', 55)
        ->assertJsonPath('current_page', 1)
        ->assertJsonPath('last_page', 2)
        ->assertJsonCount(50, 'data');
});

it('shows an event detail page with its payload', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'location_label' => 'London, UK',
        'payload' => ['name' => 'Global Tech Summit', 'location' => ['lat' => 51.5074, 'lng' => -0.1278]],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.payload.name', 'Global Tech Summit')
            ->where('event.location_label', 'London, UK')
        );
});

it('renders the two visualization pages and the dashboard without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
    $this->get(route('dashboard'))->assertOk();
});
