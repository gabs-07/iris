<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientTask extends Model
{
    protected $fillable = [
        'patient_id', 'professional_id', 'title', 'description', 'due_date', 'status',
        'repeat', 'evidence', 'evidence_file_path', 'evidence_file_name', 'evidence_file_disk', 'evidence_file_mime', 'evidence_file_size', 'follow_up',
        'submitted_at', 'review_status', 'review_feedback', 'reviewed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'completed_at' => 'datetime',
            'description' => 'encrypted',
            'evidence' => 'encrypted',
            'follow_up' => 'encrypted',
            'review_feedback' => 'encrypted',
        ];
    }


    public function isSubmitted(): bool
    {
        return in_array($this->status, ['entregada', 'completada'], true);
    }

    public function isApproved(): bool
    {
        return $this->status === 'completada' && $this->review_status === 'aprobada';
    }

    public function canBeUnsubmitted(): bool
    {
        return $this->status === 'entregada' || $this->status === 'requiere_cambios';
    }

    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_id'); }
    public function professional(): BelongsTo { return $this->belongsTo(User::class, 'professional_id'); }
}
