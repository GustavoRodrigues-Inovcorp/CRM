<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model', 'model_id',
        'description', 'changes', 'ip',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ─── Regista log de forma estática ─── */
    public static function log(
        string $action,
        string $description,
        ?string $model = null,
        ?int $modelId = null,
        ?array $changes = null
    ): void {
        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'model'       => $model,
            'model_id'    => $modelId,
            'description' => $description,
            'changes'     => $changes,
            'ip'          => request()->ip(),
        ]);
    }
}