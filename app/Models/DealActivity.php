<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealActivity extends Model
{
    protected $fillable = [
        'deal_id', 'user_id', 'type', 'title',
        'description', 'scheduled_at', 'completed',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed'    => 'boolean',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}