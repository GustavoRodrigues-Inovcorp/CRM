<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Person extends Model
{
    protected $fillable = [
        'user_id', 'entity_id', 'name', 'email',
        'phone', 'mobile', 'position', 'notes', 'status',
    ];

    /* Entidade/empresa a que esta pessoa pertence */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /* Utilizador dono do registo (tenant) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}