<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    protected $fillable = [
        'patient_id', 'professional_id', 'folio', 'patient_name', 'diagnosis', 'medication',
        'dose', 'frequency', 'duration', 'instructions', 'status', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'patient_name' => 'encrypted',
            'diagnosis' => 'encrypted',
            'medication' => 'encrypted',
            'dose' => 'encrypted',
            'frequency' => 'encrypted',
            'duration' => 'encrypted',
            'instructions' => 'encrypted',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_id'); }
    public function professional(): BelongsTo { return $this->belongsTo(User::class, 'professional_id'); }
}
