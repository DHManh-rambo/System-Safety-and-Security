<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $table = 'access_logs';

    protected $fillable = [
        'user_id',
        'vai_tro',
        'ip',
        'url',
        'method',
        'required_roles',
        'attempted_at',
        'was_blocked',
        'threat_level',
        'blocked_reason',
        'user_agent',
        'request_payload',
        'attack_type',
    ];

    protected $casts = [
        'required_roles' => 'array',
        'attempted_at'   => 'datetime',
        'was_blocked'    => 'boolean',
    ];

    // ── Scopes ──────────────────────────────────────────────
    public function scopeHighThreat($query)
    {
        return $query->where('threat_level', 'high');
    }

    public function scopeBlocked($query)
    {
        return $query->where('was_blocked', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('attempted_at', today());
    }

    public function scopeByAttackType($query, string $type)
    {
        return $query->where('attack_type', $type);
    }
}