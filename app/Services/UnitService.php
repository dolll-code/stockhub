<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnitService
{
    public function __construct(
        protected ActivityLogger $logger
    ) {}

    /**
     * Create a new unit.
     *
     * @param  array{name: string, symbol: string, allow_decimal?: bool, description?: ?string, is_active?: bool}  $data
     */
    public function create(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): Unit
    {
        return DB::transaction(function () use ($data, $actor, $ip, $userAgent) {
            $unit = Unit::create([
                'name' => trim($data['name']),
                'symbol' => trim($data['symbol']),
                'allow_decimal' => (bool) ($data['allow_decimal'] ?? false),
                'description' => isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->logger->log(
                action: 'unit.created',
                description: "Pengguna [{$actor->name}] membuat satuan [{$unit->name}] ({$unit->symbol})",
                subject: $unit,
                actor: $actor,
                newValues: $unit->only(['name', 'symbol', 'allow_decimal', 'description', 'is_active']),
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $unit;
        });
    }

    /**
     * Update an existing unit.
     *
     * @param  array{name: string, symbol: string, allow_decimal?: bool, description?: ?string, is_active?: bool}  $data
     *
     * @throws ValidationException
     */
    public function update(Unit $unit, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): Unit
    {
        $newAllowDecimal = isset($data['allow_decimal']) ? (bool) $data['allow_decimal'] : $unit->allow_decimal;
        $newActive = isset($data['is_active']) ? (bool) $data['is_active'] : $unit->is_active;

        // BR-25: allow_decimal cannot be modified once unit is used by any product
        if ($newAllowDecimal !== (bool) $unit->allow_decimal && $unit->products()->exists()) {
            throw ValidationException::withMessages([
                'allow_decimal' => ['Pengaturan desimal satuan tidak dapat diubah karena satuan sudah digunakan oleh barang.'],
            ]);
        }

        // BR-04: Cannot deactivate if used by active products
        if ($unit->is_active && ! $newActive) {
            $activeCount = $unit->products()->where('is_active', true)->count();
            if ($activeCount > 0) {
                throw ValidationException::withMessages([
                    'is_active' => ["Satuan masih dipakai {$activeCount} barang aktif."],
                ]);
            }
        }

        return DB::transaction(function () use ($unit, $data, $newAllowDecimal, $newActive, $actor, $ip, $userAgent) {
            $oldValues = $unit->only(['name', 'symbol', 'allow_decimal', 'description', 'is_active']);

            $unit->update([
                'name' => trim($data['name']),
                'symbol' => trim($data['symbol']),
                'allow_decimal' => $newAllowDecimal,
                'description' => isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null,
                'is_active' => $newActive,
            ]);

            $newValues = $unit->only(['name', 'symbol', 'allow_decimal', 'description', 'is_active']);

            $this->logger->log(
                action: 'unit.updated',
                description: "Pengguna [{$actor->name}] memperbarui satuan [{$unit->name}]",
                subject: $unit,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $unit;
        });
    }

    /**
     * Toggle active/inactive status of a unit (BR-04).
     *
     * @throws ValidationException
     */
    public function toggleStatus(Unit $unit, User $actor, ?string $ip = null, ?string $userAgent = null): Unit
    {
        if ($unit->is_active) {
            // BR-04: Cannot deactivate if used by active products
            $activeCount = $unit->products()->where('is_active', true)->count();
            if ($activeCount > 0) {
                throw ValidationException::withMessages([
                    'status' => ["Satuan masih dipakai {$activeCount} barang aktif."],
                ]);
            }
        }

        return DB::transaction(function () use ($unit, $actor, $ip, $userAgent) {
            $oldValues = $unit->only(['is_active']);
            $unit->is_active = ! $unit->is_active;
            $unit->save();

            $action = $unit->is_active ? 'unit.activated' : 'unit.deactivated';
            $verb = $unit->is_active ? 'mengaktifkan' : 'menonaktifkan';

            $this->logger->log(
                action: $action,
                description: "Pengguna [{$actor->name}] {$verb} satuan [{$unit->name}]",
                subject: $unit,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $unit->only(['is_active']),
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $unit;
        });
    }
}
