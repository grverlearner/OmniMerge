<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
| Lo que hizo un admin. Solo se escribe a través de
| App\Services\Admin\AdminAudit y nunca se edita: es el historial.
*/
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'target_label',
        'details',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public const LABELS = [
        'user.ban' => ['Bloqueó una cuenta', '#fb7185'],
        'user.unban' => ['Desbloqueó una cuenta', '#34d399'],
        'user.role' => ['Cambió el rol', '#a78bfa'],
        'user.badge' => ['Cambió la insignia de creador', '#38bdf8'],
        'user.sessions' => ['Cerró las sesiones', '#fbbf24'],
        'user.delete' => ['Eliminó una cuenta', '#fb7185'],
        'user.restore' => ['Recuperó una cuenta', '#34d399'],
        'content.flag' => ['Marcó contenido', '#38bdf8'],
        'content.unflag' => ['Quitó una marca', '#94a3b8'],
        'content.visibility' => ['Cambió la visibilidad', '#a78bfa'],
        'content.delete' => ['Eliminó contenido', '#fb7185'],
        'content.restore' => ['Recuperó contenido', '#34d399'],
        'settings.update' => ['Cambió la configuración', '#fbbf24'],
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id')->withTrashed();
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->action][0] ?? $this->action;
    }

    public function getToneAttribute(): string
    {
        return self::LABELS[$this->action][1] ?? '#94a3b8';
    }
}
