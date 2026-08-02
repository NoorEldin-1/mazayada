<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_id', 'actor_role', 'action', 'resource_type',
        'resource_id', 'details', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * $resourceId is nullable on purpose: platform-wide actions (settings saved,
     * a report exported) have no single resource row. The column itself is NOT
     * NULL, so a missing id is stored as '-'. Never let an audit-trail detail
     * throw — a TypeError here used to 500 the whole request that it was only
     * meant to record.
     */
    public static function log(string $action, string $resourceType, ?string $resourceId = null, ?string $actorId = null, ?string $actorRole = null, ?array $details = null, ?string $ip = null): static
    {
        return static::create([
            'actor_id' => $actorId ?? auth()->id() ?? 'system',
            'actor_role' => $actorRole ?? (auth()->user()?->role?->value ?? 'SYSTEM'),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId ?? '-',
            'details' => $details,
            'ip_address' => $ip ?? request()->ip(),
            'created_at' => now(),
        ]);
    }
}
