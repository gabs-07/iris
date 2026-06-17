<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaryEntry extends Model
{
    protected $fillable = ['patient_id', 'title', 'content', 'notes', 'mood', 'emoji', 'entry_date', 'authorized_professional_id', 'authorized_at'];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'content' => 'encrypted',
            'notes' => 'encrypted:array',
            'authorized_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function authorizedProfessional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_professional_id');
    }
}

