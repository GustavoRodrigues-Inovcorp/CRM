<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\Loggable;

class Deal extends Model
{
    use Loggable;

    protected $fillable = [
        'user_id', 'entity_id', 'person_id', 'title',
        'value', 'stage', 'probability',
        'expected_close_date', 'sort_order', 'notes',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'value'               => 'decimal:2',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'deal_products')
            ->withPivot('quantity', 'unit_price', 'total')
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DealActivity::class)->latest();
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(DealProposal::class)->latest();
    }

    public function followUp(): HasOne
    {
        return $this->hasOne(DealFollowUp::class)->latest();
    }
}