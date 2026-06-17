<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalConsent extends Model
{
    protected $fillable = [
        'user_id', 'acepta_terminos', 'acepta_privacidad', 'acepta_datos_sensibles',
        'acepta_comunicaciones', 'acepta_condiciones_profesionales', 'declara_veracidad_profesional',
        'accepted_at', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'acepta_terminos' => 'boolean',
            'acepta_privacidad' => 'boolean',
            'acepta_datos_sensibles' => 'boolean',
            'acepta_comunicaciones' => 'boolean',
            'acepta_condiciones_profesionales' => 'boolean',
            'declara_veracidad_profesional' => 'boolean',
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
