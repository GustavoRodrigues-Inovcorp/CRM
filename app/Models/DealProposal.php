<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealProposal extends Model
{
    protected $fillable = [
        'deal_id', 'user_id', 'file_path',
        'file_name', 'file_size', 'sent_at', 'sent_to_email',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
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