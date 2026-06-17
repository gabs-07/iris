<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    protected $fillable = ['user_id', 'nombre', 'relacion', 'telefono'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
