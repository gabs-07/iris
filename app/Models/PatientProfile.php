<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProfile extends Model
{
    protected $fillable = [
        'user_id', 'terapia_previa', 'medicacion_actual', 'motivo_consulta', 'objetivos',
        'ocupacion', 'domicilio', 'estado_civil', 'antecedentes', 'alergias',
        'clinical_history', 'clinical_attachments',
    ];

    protected function casts(): array
    {
        return [
            'motivo_consulta' => 'encrypted',
            'objetivos' => 'encrypted',
            'antecedentes' => 'encrypted',
            'alergias' => 'encrypted',
            'medicacion_actual' => 'encrypted',
            'clinical_history' => 'encrypted:array',
            'clinical_attachments' => 'encrypted:array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
