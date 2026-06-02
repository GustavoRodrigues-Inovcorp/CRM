<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'name', 'description', 'price', 'unit', 'active',
    ];

    protected $casts = [
        'price'  => 'decimal:2',
        'active' => 'boolean',
    ];

    /* Negócios que têm este produto */
    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'deal_products')
            ->withPivot('quantity', 'unit_price', 'total')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}