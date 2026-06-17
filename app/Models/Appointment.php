<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\AppointmentLifecycleService;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'professional_id', 'folio', 'reason', 'modality', 'appointment_date',
        'appointment_time', 'starts_at', 'ends_at', 'missed_at', 'notes', 'status', 'payment_status',
        'amount', 'room_link', 'zoom_meeting_id', 'zoom_join_url', 'zoom_start_url',
        'zoom_password', 'zoom_created_at', 'zoom_payload', 'requested_by',
        'reschedule_proposal', 'reschedule_date', 'reschedule_time', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'reschedule_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'missed_at' => 'datetime',
            'amount' => 'decimal:2',
            'zoom_created_at' => 'datetime',
            'zoom_payload' => 'array',
            'reason' => 'encrypted',
            'notes' => 'encrypted',
            'reschedule_proposal' => 'encrypted',
            'cancel_reason' => 'encrypted',
        ];
    }


    public function getSessionAccessStartsAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->starts_at?->copy()->subMinutes(AppointmentLifecycleService::SESSION_EARLY_ACCESS_MINUTES);
    }

    public function getSessionAccessEndsAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->starts_at?->copy()->addMinutes(AppointmentLifecycleService::SESSION_ACCESS_MINUTES);
    }

    public function getIsVideoSessionAvailableAttribute(): bool
    {
        $now = now(config('app.timezone'));

        return $this->status === 'accepted'
            && $this->starts_at
            && $this->session_access_starts_at?->lte($now)
            && $this->session_access_ends_at?->gte($now);
    }

    public function getIsPendingOrActiveSessionAttribute(): bool
    {
        if ($this->status !== 'accepted') {
            return false;
        }

        if (! $this->starts_at) {
            return true;
        }

        return $this->session_access_ends_at?->gte(now(config('app.timezone'))) ?? true;
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'accepted' && $this->starts_at && $this->session_access_ends_at?->lt(now(config('app.timezone')))) {
            return 'missed';
        }

        return (string) $this->status;
    }

    public function getPatientVideoUrlAttribute(): ?string
    {
        $url = $this->zoom_join_url ?: $this->room_link;

        return $this->withZoomDisplayName($url, $this->patient?->nombre_completo ?: $this->patient?->name ?: 'Paciente IRIS');
    }

    public function getProfessionalVideoUrlAttribute(): ?string
    {
        $url = $this->zoom_start_url ?: $this->room_link ?: $this->zoom_join_url;

        return $this->withZoomDisplayName($url, $this->professional?->nombre_completo ?: $this->professional?->name ?: 'Especialista IRIS');
    }

    private function withZoomDisplayName(?string $url, ?string $displayName): ?string
    {
        if (! $url || ! $displayName || ! str_contains(parse_url($url, PHP_URL_HOST) ?: '', 'zoom.us')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['uname'] = $displayName;
        $query['display_name'] = $displayName;

        $rebuilt = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        if ($queryString !== '') {
            $rebuilt .= '?'.$queryString;
        }

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function sessionNotes(): HasMany
    {
        return $this->hasMany(SessionNote::class);
    }
}
