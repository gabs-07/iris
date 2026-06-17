<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id', 'patient_id', 'auditable_type', 'auditable_id', 'action',
        'description', 'ip_address', 'user_agent', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_id'); }
}
