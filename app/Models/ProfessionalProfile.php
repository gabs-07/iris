<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalProfile extends Model
{
    protected $fillable = [
        'user_id', 'tipo_profesional', 'titulo_profesional', 'cedula_profesional', 'cedula_especialidad',
        'institucion', 'posgrado', 'especialidad_principal', 'experiencia_anios', 'asociaciones',
        'enfoques', 'poblaciones', 'areas', 'modalidad', 'ubicacion', 'idiomas', 'biografia',
        'servicios', 'presentacion', 'formacion_academica', 'especialidades', 'dias_atencion',
        'proximo_espacio', 'costo_min', 'costo_max', 'duracion_sesion', 'disponibilidad', 'modo_escucha_activo', 'modo_escucha_activado_at',
        'documentos', 'submitted_at', 'approved_at', 'rejected_at', 'rejection_reason', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'enfoques' => 'array',
            'poblaciones' => 'array',
            'areas' => 'array',
            'disponibilidad' => 'array',
            'modo_escucha_activo' => 'boolean',
            'modo_escucha_activado_at' => 'datetime',
            'formacion_academica' => 'array',
            'especialidades' => 'array',
            'dias_atencion' => 'array',
            'documentos' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
