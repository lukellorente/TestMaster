<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de acciones de administración: quién hizo qué, a qué y cuándo.
 * `details` guarda solo ids y valores cambiados, nunca datos personales.
 */
class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_id', 'action', 'target_type', 'target_id', 'details', 'created_at'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function record(User $admin, string $action, string $targetType, ?int $targetId, array $details = []): self
    {
        return self::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details ?: null,
            'created_at' => now(),
        ]);
    }
}
