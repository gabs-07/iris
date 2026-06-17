<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientNote extends Model
{
    protected $fillable = ['patient_id', 'professional_id', 'title', 'note_date', 'type', 'description'];

    protected function casts(): array
    {
        return ['note_date' => 'date', 'description' => 'encrypted'];
    }

    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_id'); }
    public function professional(): BelongsTo { return $this->belongsTo(User::class, 'professional_id'); }
}
