<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealFollowUp extends Model
{
    protected $fillable = [
        'deal_id', 'user_id', 'email',
        'active', 'emails_sent', 'next_send_at', 'cancelled_at',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'next_send_at' => 'datetime',
        'cancelled_at' => 'datetime',
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