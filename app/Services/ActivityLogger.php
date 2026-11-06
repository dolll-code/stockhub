<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Keys that must never be recorded in activity logs.
     *
     * @var list<string>
     */
    protected array $redactedKeys = [
        'password',
        'password_confirmation',
        'token',
        'remember_token',
        'app_key',
        'api_key',
        'secret',
        'current_password',
        'new_password',
    ];

    /**
     * Record an immutable activity log entry.
     *
     * Note: Per PRD §9.2, this service does NOT read request() or session() directly.
     * Metadata must be passed explicitly from the caller boundary (Controller / Command).
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?User $actor = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'old_values' => $oldValues !== null ? $this->filterSensitiveData($oldValues) : null,
            'new_values' => $newValues !== null ? $this->filterSensitiveData($newValues) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent !== null ? substr($userAgent, 0, 255) : null,
        ]);
    }

    /**
     * Recursively remove sensitive keys from logged data payloads.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterSensitiveData(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $this->redactedKeys, true)) {
                continue;
            }

            if (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
