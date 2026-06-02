<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entity extends Model
{
    protected $fillable = [
        'user_id', 'name', 'vat', 'email',
        'phone', 'address', 'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}