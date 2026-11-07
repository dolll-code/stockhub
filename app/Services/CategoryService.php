<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(
        protected ActivityLogger $logger
    ) {}

    /**
     * Create a new category.
     *
     * @param  array{name: string, description?: ?string, is_active?: bool}  $data
     */
    public function create(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): Category
    {
        return DB::transaction(function () use ($data, $actor, $ip, $userAgent) {
            $category = Category::create([
                'name' => trim($data['name']),
                'description' => isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->logger->log(
                action: 'category.created',
                description: "Pengguna [{$actor->name}] membuat kategori [{$category->name}]",
                subject: $category,
                actor: $actor,
                newValues: $category->only(['name', 'description', 'is_active']),
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $category;
        });
    }

    /**
     * Update an existing category.
     *
     * @param  array{name: string, description?: ?string, is_active?: bool}  $data
     *
     * @throws ValidationException
     */
    public function update(Category $category, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): Category
    {
        $newActive = isset($data['is_active']) ? (bool) $data['is_active'] : $category->is_active;

        // BR-04: Cannot deactivate if used by active products
        if ($category->is_active && ! $newActive) {
            $activeCount = $category->products()->where('is_active', true)->count();
            if ($activeCount > 0) {
                throw ValidationException::withMessages([
                    'is_active' => ["Kategori masih dipakai {$activeCount} barang aktif."],
                ]);
            }
        }

        return DB::transaction(function () use ($category, $data, $newActive, $actor, $ip, $userAgent) {
            $oldValues = $category->only(['name', 'description', 'is_active']);

            $category->update([
                'name' => trim($data['name']),
                'description' => isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null,
                'is_active' => $newActive,
            ]);

            $newValues = $category->only(['name', 'description', 'is_active']);

            $this->logger->log(
                action: 'category.updated',
                description: "Pengguna [{$actor->name}] memperbarui kategori [{$category->name}]",
                subject: $category,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $category;
        });
    }

    /**
     * Toggle active/inactive status of a category (BR-04).
     *
     * @throws ValidationException
     */
    public function toggleStatus(Category $category, User $actor, ?string $ip = null, ?string $userAgent = null): Category
    {
        if ($category->is_active) {
            // BR-04: Cannot deactivate if used by active products
            $activeCount = $category->products()->where('is_active', true)->count();
            if ($activeCount > 0) {
                throw ValidationException::withMessages([
                    'status' => ["Kategori masih dipakai {$activeCount} barang aktif."],
                ]);
            }
        }

        return DB::transaction(function () use ($category, $actor, $ip, $userAgent) {
            $oldValues = $category->only(['is_active']);
            $category->is_active = ! $category->is_active;
            $category->save();

            $action = $category->is_active ? 'category.activated' : 'category.deactivated';
            $verb = $category->is_active ? 'mengaktifkan' : 'menonaktifkan';

            $this->logger->log(
                action: $action,
                description: "Pengguna [{$actor->name}] {$verb} kategori [{$category->name}]",
                subject: $category,
                actor: $actor,
                oldValues: $oldValues,
                newValues: $category->only(['is_active']),
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $category;
        });
    }
}
