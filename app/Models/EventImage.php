<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventImage extends Model
{
    protected $guarded = [];

    protected $appends = ['url'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getUrlAttribute(): string
    {
        return asset($this->path);
    }
}
