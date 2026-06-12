<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNotification extends Model
{
    protected $table = 'crm_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'link', 'read',
    ];

    protected $casts = [
        'read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ─── Cria notificação de forma estática ─── */
    public static function notify(int $userId, string $type, string $title, string $message = '', string $link = ''): void
    {
        static::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
        ]);
    }
}