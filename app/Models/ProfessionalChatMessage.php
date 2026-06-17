<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalChatMessage extends Model
{
    protected $fillable = ['user_id', 'message', 'tags'];

    protected function casts(): array
    {
        return [
            'message' => 'encrypted',
            'tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
