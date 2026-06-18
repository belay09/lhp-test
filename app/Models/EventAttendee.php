<?php

namespace App\Models;

use Database\Factories\EventAttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendee extends Model
{
    /** @use HasFactory<EventAttendeeFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'confirmed_at',
        'reminded_3d_at',
        'reminded_24h_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'reminded_3d_at' => 'datetime',
            'reminded_24h_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
