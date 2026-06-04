<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFormSubmission extends Model
{
    protected $fillable = [
        'lead_form_id', 'entity_id', 'data', 'ip', 'origin',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(LeadForm::class, 'lead_form_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}