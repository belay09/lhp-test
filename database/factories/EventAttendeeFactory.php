<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAttendee>
 */
class EventAttendeeFactory extends Factory
{
    protected $model = EventAttendee::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'confirmed_at' => now(),
        ];
    }
}
