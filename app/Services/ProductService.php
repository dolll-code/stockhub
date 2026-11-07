<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected StockService $stockService,
        protected ActivityLogger $logger
    ) {}

    /**
     * Create a new product (FR-PRD-02, BR-05, BR-17).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException|Throwable
     */
    public function create(array $data, ?UploadedFile $photo, User $actor, ?string $ip = null, ?string $userAgent = null): Product
    {
        $uploadedPath = null;

        if ($photo !== null) {
            $uploadedPath = $this->storePhoto($photo);
        }

        try {
            return DB::transaction(function () use ($data, $uploadedPath, $actor, $ip, $userAgent) {
                // Determine product code: auto or manual
                $code = isset($data['code']) && trim((string) $data['code']) !== ''
                    ? strtoupper(trim((string) $data['code']))
                    : $this->documentNumberService->generateProductCode();

                $unit = Unit::findOrFail($data['unit_id']);
                $openingStock = isset($data['opening_stock']) ? (float) $data['opening_stock'] : 0.0;

                // Validate decimal precision against unit (BR-01)
                $this->validateQuantityDecimal($unit, $openingStock, 'opening_stock', 'Stok awal');
                if (isset($data['min_stock'])) {
                    $this->validateQuantityDecimal($unit, (float) $data['min_stock'], 'min_stock', 'Stok minimum');
                }

                // Create product with current_stock = 0 (BR-06)
                $product = new Product;
                $product->code = $code;
                $product->name = trim((string) $data['name']);
                $product->category_id = (int) $data['category_id'];
                $product->unit_id = (int) $data['unit_id'];
                $product->main_supplier_id = ! empty($data['main_supplier_id']) ? (int) $data['main_supplier_id'] : null;
                $product->purchase_price = isset($data['purchase_price']) ? (float) $data['purchase_price'] : 0.00;
                $product->selling_price = isset($data['selling_price']) && $data['selling_price'] !== '' ? (float) $data['selling_price'] : null;
                $product->min_stock = isset($data['min_stock']) ? (float) $data['min_stock'] : 0.000;
                $product->storage_location = isset($data['storage_location']) && trim((string) $data['storage_location']) !== '' ? trim((string) $data['storage_location']) : null;
                $product->description = isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null;
                $product->photo_path = $uploadedPath;
                $product->is_active = (bool) ($data['is_active'] ?? true);
                $product->created_by = $actor->id;
                $product->save();

                // BR-17, AC-PRD-01: If opening_stock > 0, record opening_balance movement via StockService
                if ($openingStock > 0) {
                    $this->stockService->recordOpeningBalance($product, $openingStock, $actor);
                }

                $this->logger->log(
                    action: 'product.created',
                    description: "Pengguna [{$actor->name}] membuat barang [{$product->name}] ({$product->code})",
                    subject: $product,
                    actor: $actor,
                    newValues: [
                        'code' => $product->code,
                        'name' => $product->name,
                        'category_id' => $product->category_id,
                        'unit_id' => $product->unit_id,
                        'main_supplier_id' => $product->main_supplier_id,
                        'current_stock' => (float) $product->current_stock,
                        'min_stock' => (float) $product->min_stock,
                        'is_active' => $product->is_active,
                    ],
                    ipAddress: $ip,
                    userAgent: $userAgent
                );

                return $product;
            });
        } catch (Throwable $e) {
            // Clean up uploaded file if transaction fails (AC-PRD-02)
            if ($uploadedPath !== null) {
                $this->deletePhotoFile($uploadedPath);
            }
            throw $e;
        }
    }

    /**
     * Update an existing product (FR-PRD-03, BR-05, BR-25).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(
        Product $product,
        array $data,
        ?UploadedFile $photo,
        bool $removePhoto,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): Product {
        // BR-25: Unit is locked once product has movements
        $targetUnitId = (int) $data['unit_id'];
        if ($targetUnitId !== (int) $product->unit_id && $product->stockMovements()->exists()) {
            throw ValidationException::withMessages([
                'unit_id' => ['Satuan barang tidak dapat diubah karena barang sudah memiliki riwayat mutasi stok.'],
            ]);
        }

        $unit = Unit::findOrFail($targetUnitId);
        if (isset($data['min_stock'])) {
            $this->validateQuantityDecimal($unit, (float) $data['min_stock'], 'min_stock', 'Stok minimum');
        }

        $newPhotoPath = $product->photo_path;
        $oldPhotoToDelete = null;

        if ($removePhoto && $product->photo_path) {
            $oldPhotoToDelete = $product->photo_path;
            $newPhotoPath = null;
        } elseif ($photo !== null) {
            $oldPhotoToDelete = $product->photo_path;
            $newPhotoPath = $this->storePhoto($photo);
        }

        try {
            return DB::transaction(function () use ($product, $data, $newPhotoPath, $oldPhotoToDelete, $actor, $ip, $userAgent) {
                $oldValues = [
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'unit_id' => $product->unit_id,
                    'main_supplier_id' => $product->main_supplier_id,
                    'purchase_price' => (float) $product->purchase_price,
                    'selling_price' => $product->selling_price !== null ? (float) $product->selling_price : null,
                    'min_stock' => (float) $product->min_stock,
                    'storage_location' => $product->storage_location,
                    'photo_path' => $product->photo_path,
                    'is_active' => $product->is_active,
                ];

                // Note: code is immutable (BR-05); current_stock is never updated here (BR-06)
                $product->name = trim((string) $data['name']);
                $product->category_id = (int) $data['category_id'];
                $product->unit_id = (int) $data['unit_id'];
                $product->main_supplier_id = ! empty($data['main_supplier_id']) ? (int) $data['main_supplier_id'] : null;

                if (array_key_exists('purchase_price', $data)) {
                    $product->purchase_price = (float) $data['purchase_price'];
                }
                if (array_key_exists('selling_price', $data)) {
                    $product->selling_price = $data['selling_price'] !== '' && $data['selling_price'] !== null ? (float) $data['selling_price'] : null;
                }

                $product->min_stock = isset($data['min_stock']) ? (float) $data['min_stock'] : 0.000;
                $product->storage_location = isset($data['storage_location']) && trim((string) $data['storage_location']) !== '' ? trim((string) $data['storage_location']) : null;
                $product->description = isset($data['description']) && trim((string) $data['description']) !== '' ? trim((string) $data['description']) : null;
                $product->photo_path = $newPhotoPath;
                $product->updated_by = $actor->id;
                $product->save();

                if ($oldPhotoToDelete !== null && $oldPhotoToDelete !== $newPhotoPath) {
                    $this->deletePhotoFile($oldPhotoToDelete);
                }

                $this->logger->log(
                    action: 'product.updated',
                    description: "Pengguna [{$actor->name}] memperbarui data barang [{$product->name}] ({$product->code})",
                    subject: $product,
                    actor: $actor,
                    oldValues: $oldValues,
                    newValues: [
                        'name' => $product->name,
                        'category_id' => $product->category_id,
                        'unit_id' => $product->unit_id,
                        'main_supplier_id' => $product->main_supplier_id,
                        'purchase_price' => (float) $product->purchase_price,
                        'selling_price' => $product->selling_price !== null ? (float) $product->selling_price : null,
                        'min_stock' => (float) $product->min_stock,
                        'storage_location' => $product->storage_location,
                        'photo_path' => $product->photo_path,
                        'is_active' => $product->is_active,
                    ],
                    ipAddress: $ip,
                    userAgent: $userAgent
                );

                return $product;
            });
        } catch (Throwable $e) {
            // Clean up newly uploaded file if update transaction failed
            if ($photo !== null && $newPhotoPath !== null) {
                $this->deletePhotoFile($newPhotoPath);
            }
            throw $e;
        }
    }

    /**
     * Toggle active/inactive status of a product (BR-04, AC-PRD-04).
     *
     * @throws ValidationException
     */
    public function toggleStatus(Product $product, User $actor, ?string $ip = null, ?string $userAgent = null): Product
    {
        // BR-04: Cannot deactivate product if current_stock > 0
        if ($product->is_active && (float) $product->current_stock > 0) {
            $formattedStock = rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',');
            $symbol = $product->unit->symbol;
            throw ValidationException::withMessages([
                'status' => ["Barang tidak dapat dinonaktifkan karena masih memiliki stok ({$formattedStock} {$symbol}). Stok harus 0."],
            ]);
        }

        return DB::transaction(function () use ($product, $actor, $ip, $userAgent) {
            $oldValues = ['is_active' => $product->is_active];
            $product->is_active = ! $product->is_active;
            $product->updated_by = $actor->id;
            $product->save();

            $action = $product->is_active ? 'product.activated' : 'product.deactivated';
            $verb = $product->is_active ? 'mengaktifkan' : 'menonaktifkan';

            $this->logger->log(
                action: $action,
                description: "Pengguna [{$actor->name}] {$verb} barang [{$product->name}] ({$product->code})",
                subject: $product,
                actor: $actor,
                oldValues: $oldValues,
                newValues: ['is_active' => $product->is_active],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $product;
        });
    }

    /**
     * Delete product photo.
     */
    public function deletePhoto(Product $product, User $actor, ?string $ip = null, ?string $userAgent = null): Product
    {
        if (! $product->photo_path) {
            return $product;
        }

        return DB::transaction(function () use ($product, $actor, $ip, $userAgent) {
            $oldPath = $product->photo_path;
            $product->photo_path = null;
            $product->updated_by = $actor->id;
            $product->save();

            $this->deletePhotoFile($oldPath);

            $this->logger->log(
                action: 'product.photo_deleted',
                description: "Pengguna [{$actor->name}] menghapus foto barang [{$product->name}] ({$product->code})",
                subject: $product,
                actor: $actor,
                oldValues: ['photo_path' => $oldPath],
                newValues: ['photo_path' => null],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $product;
        });
    }

    /**
     * Validate quantity decimal rules per unit (BR-01).
     *
     * @throws ValidationException
     */
    public function validateQuantityDecimal(Unit $unit, float $qty, string $fieldKey, string $fieldLabel): void
    {
        if ($qty < 0) {
            throw ValidationException::withMessages([
                $fieldKey => ["{$fieldLabel} tidak boleh bernilai negatif."],
            ]);
        }

        if (! $unit->allow_decimal) {
            if ($qty != (int) $qty) {
                throw ValidationException::withMessages([
                    $fieldKey => ["{$fieldLabel} harus bilangan bulat untuk satuan {$unit->name} ({$unit->symbol})."],
                ]);
            }
        } else {
            $str = (string) $qty;
            $parts = explode('.', $str);
            if (isset($parts[1]) && strlen($parts[1]) > 3) {
                throw ValidationException::withMessages([
                    $fieldKey => ["{$fieldLabel} maksimal memiliki 3 angka desimal untuk satuan {$unit->name}."],
                ]);
            }
        }
    }

    /**
     * Securely store photo in storage/app/public/products.
     *
     * @throws ValidationException
     */
    protected function storePhoto(UploadedFile $file): string
    {
        $realPath = $file->getRealPath();

        // 1. Validate real image and dimensions (100x100 to 4000x4000)
        $imageInfo = @getimagesize($realPath);
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'photo' => ['File yang diunggah bukan gambar valid atau file rusak.'],
            ]);
        }

        [$width, $height] = $imageInfo;
        if ($width < 100 || $height < 100 || $width > 4000 || $height > 4000) {
            throw ValidationException::withMessages([
                'photo' => ["Dimensi gambar ({$width}x{$height} px) di luar batas yang diizinkan (minimal 100x100 px, maksimal 4000x4000 px)."],
            ]);
        }

        // 2. Validate real MIME type (reject SVG and fake extensions)
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $detectedMime = $file->getMimeType();
        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'photo' => ['Format gambar harus JPG, JPEG, PNG, atau WebP. Format SVG atau lainnya tidak diizinkan.'],
            ]);
        }

        // 3. Generate random filename
        $extension = match ($detectedMime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => 'webp',
        };

        $filename = Str::random(40).'.'.$extension;
        $path = $file->storeAs('products', $filename, 'public');

        if (! $path) {
            throw new \RuntimeException('Gagal menyimpan file foto ke media penyimpanan.');
        }

        return $path;
    }

    /**
     * Delete photo from public disk.
     */
    protected function deletePhotoFile(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
