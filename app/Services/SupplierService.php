<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected ActivityLogger $logger
    ) {}

    /**
     * Create a new supplier with auto-generated immutable code SUP-NNNN (BR-05, BR-19).
     *
     * @param  array{name: string, contact_name?: ?string, phone?: ?string, email?: ?string, address?: ?string, notes?: ?string, is_active?: bool}  $data
     */
    public function create(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): Supplier
    {
        return DB::transaction(function () use ($data, $actor, $ip, $userAgent) {
            $code = $this->documentNumberService->generateSupplierCode();

            $supplier = Supplier::create([
                'code' => $code,
                'name' => trim($data['name']),
                'contact_name' => isset($data['contact_name']) && trim((string) $data['contact_name']) !== '' ? trim((string) $data['contact_name']) : null,
                'phone' => isset($data['phone']) && trim((string) $data['phone']) !== '' ? trim((string) $data['phone']) : null,
                'email' => isset($data['email']) && trim((string) $data['email']) !== '' ? trim((string) $data['email']) : null,
                'address' => isset($data['address']) && trim((string) $data['address']) !== '' ? trim((string) $data['address']) : null,
                'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->logger->log(
                action: 'supplier.created',
                description: "Pengguna [{$actor->name}] membuat supplier [{$supplier->name}] ({$supplier->code})",
                subject: $supplier,
                actor: $actor,
                newValues: $supplier->only(['code', 'name', 'contact_name', 'phone', 'email', 'is_active']),
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $supplier;
        });
    }

    /**
     * Update an existing supplier. Code is immutable (BR-05).
     *
     * @param  array{name: string, contact_name?: ?string, phone?: ?string, email?: ?string, address?: ?string, notes?: ?string, is_active?: bool}  $data
     */
    public function update(Supplier $supplier, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): Supplier
    {
        return DB::transaction(function () use ($supplier, $data, $actor, $ip, $userAgent) {
            $oldValues = $supplier->only(['name', 'contact_name', 'phone', 'email', 'address', 'notes', 'is_active']);

            $supplier->update([
                'name' => trim($data['name']),
                'contact_name' => isset($data['contact_name']) && trim((string) $data['contact_name']) !== '' ? trim((string) $data['contact_name']) : null,
                'phone' => isset($data['phone']) && trim((string) $data['phone']) !== '' ? trim((string) $data['phone']) : null,
                'email' => isset($data['email']) && trim((string) $data['email']) !== '' ? trim((string) $data['email']) : null,
                'address' => isset($data['address']) && trim((string) $data['address']) !== '' ? trim((string) $data['address']) : null,
                'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $supplier->is_active,
            ]);

            $newValues = $supplier->only(['name', 'contact_name', 'phone', 'email', 'address', 'notes', 'is_active']);

            $this->logger->log(
                action: 'supplier.updated',
                description: "Pengguna [{$actor->name}] memperbarui data supplier [{$supplier->name}] ({$supplier->code})",
                subject: $supplier,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $supplier;
        });
    }

    /**
     * Toggle active/inactive status of a supplier (BR-04: supplier can be deactivated anytime).
     */
    public function toggleStatus(Supplier $supplier, User $actor, ?string $ip = null, ?string $userAgent = null): Supplier
    {
        return DB::transaction(function () use ($supplier, $actor, $ip, $userAgent) {
            $oldValues = $supplier->only(['is_active']);
            $supplier->is_active = ! $supplier->is_active;
            $supplier->save();

            $action = $supplier->is_active ? 'supplier.activated' : 'supplier.deactivated';
            $verb = $supplier->is_active ? 'mengaktifkan' : 'menonaktifkan';

            $this->logger->log(
                action: $action,
                description: "Pengguna [{$actor->name}] {$verb} supplier [{$supplier->name}] ({$supplier->code})",
                subject: $supplier,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $supplier->only(['is_active']),
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $supplier;
        });
    }
}
