<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionNote extends Model
{
    protected $fillable = ['appointment_id', 'professional_id', 'patient_id', 'note_type', 'content'];

    protected function casts(): array
    {
        return ['content' => 'encrypted'];
    }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function professional(): BelongsTo { return $this->belongsTo(User::class, 'professional_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_id'); }
}
