<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicalAudit
{
    public static function log(string $action, ?int $patientId = null, ?Model $auditable = null, ?string $description = null, array $metadata = []): void
    {
        try {
            /** @var Request|null $request */
            $request = request();
            AuditLog::create([
                'actor_id' => Auth::id(),
                'patient_id' => $patientId,
                'auditable_type' => $auditable ? $auditable::class : null,
                'auditable_id' => $auditable?->getKey(),
                'action' => $action,
                'description' => $description,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1000) : null,
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable) {
            // La auditoría nunca debe romper el flujo principal.
        }
    }
}
