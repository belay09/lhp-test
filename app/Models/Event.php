<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EventImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('sort_order');
    }

    /** @return HasMany<EventAttendee, $this> */
    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function displayName(): string
    {
        $name = data_get($this->payload, 'name');

        return is_string($name) && $name !== '' ? $name : $this->type;
    }

    public function startsAtTimestamp(): ?int
    {
        $startsAt = data_get($this->payload, 'schedule.starts_at');

        if (is_numeric($startsAt)) {
            return (int) $startsAt;
        }

        return $this->created_time !== null ? (int) $this->created_time : null;
    }

    public function startsAt(): ?Carbon
    {
        $timestamp = $this->startsAtTimestamp();

        return $timestamp !== null
            ? Carbon::createFromTimestamp($timestamp, 'UTC')
            : null;
    }
}
