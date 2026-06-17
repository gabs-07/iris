<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan', 'amount', 'cycle', 'status', 'starts_at', 'ends_at', 'features'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'amount' => 'decimal:2'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
