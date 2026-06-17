<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'appointment_id', 'subscription_id', 'concept', 'amount', 'currency',
        'status', 'method', 'provider', 'paid_at', 'reference', 'provider_order_id',
        'provider_capture_id', 'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'provider_payload' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}
