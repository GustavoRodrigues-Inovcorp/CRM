<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait Loggable
{
    /* ─── Regista automaticamente criação, edição e eliminação ─── */
    public static function bootLoggable(): void
    {
        /* Regista criação */
        static::created(function ($model) {
            ActivityLog::log(
                'create',
                'Criou ' . class_basename($model) . ': ' . ($model->name ?? $model->title ?? '#' . $model->id),
                class_basename($model),
                $model->id,
            );
        });

        /* Regista edição com as alterações */
        static::updated(function ($model) {
            $dirty = $model->getDirty();
            /* Ignora campos de auditoria */
            unset($dirty['updated_at'], $dirty['sort_order']);

            if (empty($dirty)) return;

            $changes = [];
            foreach ($dirty as $field => $newValue) {
                $changes[$field] = [
                    'from' => $model->getOriginal($field),
                    'to'   => $newValue,
                ];
            }

            ActivityLog::log(
                'update',
                'Atualizou ' . class_basename($model) . ': ' . ($model->name ?? $model->title ?? '#' . $model->id),
                class_basename($model),
                $model->id,
                $changes,
            );
        });

        /* Regista eliminação */
        static::deleted(function ($model) {
            ActivityLog::log(
                'delete',
                'Eliminou ' . class_basename($model) . ': ' . ($model->name ?? $model->title ?? '#' . $model->id),
                class_basename($model),
                $model->id,
            );
        });
    }
}