<?php

namespace Database\Seeders;

use App\Support\CityAnchors;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EventSeeder extends Seeder
{
    /**
     * Approximate encoded size of a single payload, in bytes. Dial this to
     * change the on-disk footprint of the seeded dataset.
     */
    public const PAYLOAD_AVG_BYTES = 1500;

    public const NUM_USERS = 3000;

    private const CHUNK = 4000;

    /** Event categories (stored in the `type` column). */
    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    private const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    /** Reusable placeholder paths under public/. */
    private const IMAGE_PATHS = [
        'images/events/concert-1.jpg',
        'images/events/concert-2.jpg',
        'images/events/conference-1.jpg',
        'images/events/festival-1.png',
    ];

    private const NAME_ADJECTIVES = ['Annual', 'Global', 'Summer', 'Winter', 'Underground', 'Open', 'International', 'Live', 'Midnight', 'Sunset', 'Urban', 'Indie', 'Grand', 'Pop-up', 'Virtual'];

    private const NAME_THEMES = ['Synthwave', 'Founders', 'Jazz', 'Tech', 'Food & Wine', 'Yoga', 'Startup', 'Design', 'Climate', 'Gaming', 'Film', 'Book', 'Marathon', 'Comedy', 'Art'];

    private const NAME_FORMATS = ['Festival', 'Meetup', 'Conference', 'Summit', 'Workshop', 'Expo', 'Showcase', 'Gala', 'Jam', 'Retreat', 'Fair', 'Night', 'Tour', 'Symposium', 'Block Party'];

    public function run(): void
    {
        $rows = (int) (env('SEED_ROWS', 1_250_000));

        $this->command?->info("Seeding {$rows} events...");

        $start = microtime(true);

        $this->withSeedingPragmas(function () use ($rows) {
            $this->ensureUsers();
            $this->insertEvents($rows);
        });

        $elapsed = round(microtime(true) - $start, 1);
        $rate = $elapsed > 0 ? round($rows / $elapsed) : $rows;
        $this->command?->info("Done. {$rows} events in {$elapsed}s ({$rate} rows/s).");
    }

    /**
     * Bulk-insert $count event rows using cheap, template-driven payloads.
     * Reused by the perf tests to top up the dataset to a target size.
     */
    public function insertEvents(int $count): void
    {
        $this->ensureUsers();

        DB::connection()->disableQueryLog();

        $template = $this->payloadTemplate();
        $now = date('Y-m-d H:i:s');
        $userMax = self::NUM_USERS;

        $year = 365 * 24 * 60 * 60;
        $now_ts = time();
        // Event start times span roughly one year in the past to one year out.
        $startTime = $now_ts - $year;
        $endTime = $now_ts + $year;

        $typeWeights = $this->cumulativeWeights([20, 14, 22, 12, 12, 8, 8, 4]);
        $statusWeights = $this->cumulativeWeights([12, 70, 8, 10]);
        $anchors = CityAnchors::all();
        $anchorCount = count($anchors);

        $remaining = $count;
        $done = 0;

        while ($remaining > 0) {
            $batchSize = min(self::CHUNK, $remaining);
            $batch = [];
            $imageBatch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $type = self::TYPES[$this->pick($typeWeights)];
                $status = self::STATUSES[$this->pick($statusWeights)];
                $startsAt = mt_rand($startTime, $endTime);
                $endsAt = $startsAt + mt_rand(3600, 3 * 24 * 3600);

                $anchor = $anchors[mt_rand(0, $anchorCount - 1)];
                $latitude = round($anchor['lat'] + (mt_rand(-500, 500) / 1000), 7);
                $longitude = round($anchor['lng'] + (mt_rand(-500, 500) / 1000), 7);

                $name = self::NAME_ADJECTIVES[array_rand(self::NAME_ADJECTIVES)]
                    .' '.self::NAME_THEMES[array_rand(self::NAME_THEMES)]
                    .' '.self::NAME_FORMATS[array_rand(self::NAME_FORMATS)];

                $payload = strtr($template, [
                    '{{NAME}}' => $this->escape($name),
                    '{{CATEGORY}}' => $type,
                    '{{ORGANIZER}}' => 'Organizer '.mt_rand(1, 9999),
                    '{{VENUE}}' => $this->escape($this->venueName()),
                    '{{LAT}}' => (string) $latitude,
                    '{{LNG}}' => (string) $longitude,
                    '{{STARTS}}' => (string) $startsAt,
                    '{{ENDS}}' => (string) $endsAt,
                    '{{CAPACITY}}' => (string) mt_rand(20, 50000),
                    '{{PRICE}}' => (string) (mt_rand(0, 25000) / 100),
                ]);

                $eventId = $this->uuidv4();

                $batch[] = [
                    'id' => $eventId,
                    'user_id' => mt_rand(1, $userMax),
                    'type' => $type,
                    'status' => $status,
                    'created_time' => $startsAt,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'location_label' => $anchor['label'],
                    'payload' => $payload,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                foreach ($this->imagesForEvent($eventId) as $sortOrder => $path) {
                    $imageBatch[] = [
                        'event_id' => $eventId,
                        'path' => $path,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::transaction(function () use ($batch, $imageBatch) {
                DB::table('events')->insert($batch);
                if ($imageBatch !== []) {
                    DB::table('event_images')->insert($imageBatch);
                }
            });

            $done += $batchSize;
            $remaining -= $batchSize;

            if ($done % (self::CHUNK * 25) === 0 || $remaining === 0) {
                $this->command?->getOutput()?->writeln("  inserted {$done}/{$count}");
            }
        }
    }

    private function ensureUsers(): void
    {
        $existing = DB::table('users')->count();
        if ($existing >= self::NUM_USERS) {
            return;
        }

        $password = Hash::make('password');
        $now = date('Y-m-d H:i:s');

        $remaining = self::NUM_USERS - $existing;
        $offset = $existing;

        while ($remaining > 0) {
            $batchSize = min(1000, $remaining);
            $batch = [];

            for ($i = 0; $i < $batchSize; $i++) {
                $n = $offset + $i + 1;
                $batch[] = [
                    'name' => "User {$n}",
                    'email' => "user{$n}@example.test",
                    'email_verified_at' => $now,
                    'password' => $password,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($batch);
            $offset += $batchSize;
            $remaining -= $batchSize;
        }
    }

    /**
     * Build a ~PAYLOAD_AVG_BYTES payload string once, with placeholder tokens
     * that are cheaply substituted per row.
     */
    private function payloadTemplate(): string
    {
        $payload = [
            'name' => '{{NAME}}',
            'category' => '{{CATEGORY}}',
            'description' => 'Join us for {{NAME}} — a {{CATEGORY}} you won\'t want to miss.',
            'organizer' => [
                'name' => '{{ORGANIZER}}',
                'verified' => true,
            ],
            'venue' => [
                'name' => '{{VENUE}}',
                'capacity' => '{{CAPACITY}}',
            ],
            'location' => [
                'lat' => '{{LAT}}',
                'lng' => '{{LNG}}',
            ],
            'schedule' => [
                'starts_at' => '{{STARTS}}',
                'ends_at' => '{{ENDS}}',
            ],
            'pricing' => [
                'currency' => 'USD',
                'min_price' => '{{PRICE}}',
            ],
            'tags' => ['live', 'in-person', 'featured', 'all-ages'],
            'notes' => '',
        ];

        $encoded = json_encode($payload);
        $pad = self::PAYLOAD_AVG_BYTES - strlen($encoded);
        if ($pad > 0) {
            $payload['notes'] = str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit. ', (int) ceil($pad / 56));
            $payload['notes'] = substr($payload['notes'], 0, $pad);
        }

        return json_encode($payload);
    }

    private function venueName(): string
    {
        $a = ['The Grand', 'Riverside', 'Downtown', 'Skyline', 'Harbor', 'Old Town', 'Central', 'Sunset'];
        $b = ['Hall', 'Arena', 'Pavilion', 'Gardens', 'Warehouse', 'Theatre', 'Rooftop', 'Stadium'];

        return $a[array_rand($a)].' '.$b[array_rand($b)];
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /** @return array<int,int> */
    private function cumulativeWeights(array $weights): array
    {
        $cumulative = [];
        $sum = 0;
        foreach ($weights as $w) {
            $sum += $w;
            $cumulative[] = $sum;
        }

        return $cumulative;
    }

    /** @param array<int,int> $cumulative */
    private function pick(array $cumulative): int
    {
        $total = end($cumulative);
        $roll = mt_rand(1, $total);
        foreach ($cumulative as $index => $threshold) {
            if ($roll <= $threshold) {
                return $index;
            }
        }

        return 0;
    }

    /** @return list<string> */
    private function imagesForEvent(string $eventId): array
    {
        $hash = crc32($eventId);
        $count = 2 + ($hash & 1);
        $pathCount = count(self::IMAGE_PATHS);
        $paths = [];

        for ($i = 0; $i < $count; $i++) {
            $paths[] = self::IMAGE_PATHS[($hash + $i * 7) % $pathCount];
        }

        return $paths;
    }

    private function withSeedingPragmas(callable $callback): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            $callback();

            return;
        }

        DB::statement('PRAGMA journal_mode = MEMORY');
        DB::statement('PRAGMA synchronous = OFF');
        DB::statement('PRAGMA temp_store = MEMORY');
        DB::statement('PRAGMA cache_size = -64000');

        try {
            $callback();
        } finally {
            DB::statement('PRAGMA journal_mode = WAL');
            DB::statement('PRAGMA synchronous = NORMAL');
        }
    }
}
