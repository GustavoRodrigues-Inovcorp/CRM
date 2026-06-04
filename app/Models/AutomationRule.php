<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRule extends Model
{
    protected $fillable = [
        'user_id', 'name', 'trigger', 'trigger_days',
        'action', 'activity_type', 'activity_title',
        'activity_description', 'notify', 'active', 'last_run_at',
    ];

    protected $casts = [
        'active'      => 'boolean',
        'notify'      => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}