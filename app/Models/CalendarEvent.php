<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'type',
        'start_at', 'end_at', 'location', 'color',
        'completed', 'eventable_type', 'eventable_id',
    ];

    protected $casts = [
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
        'completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ─── Relação polimórfica — Liga a Entity, Person ou Deal ─── */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}