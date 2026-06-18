<?php

use App\Models\Event;
use App\Services\GeocodingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:send-reminders')->hourly();

Artisan::command('events:backfill-location-labels', function (GeocodingService $geocoder) {
    $updated = 0;

    Event::query()
        ->whereNull('location_label')
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->orderBy('id')
        ->chunkById(1000, function ($events) use ($geocoder, &$updated) {
            foreach ($events as $event) {
                $event->update([
                    'location_label' => $geocoder->labelFor($event->latitude, $event->longitude),
                ]);
                $updated++;
            }

            $this->info("Updated {$updated} events...");
        });

    $this->info("Done. {$updated} events updated.");
})->purpose('Backfill location_label from lat/lng for existing events');
