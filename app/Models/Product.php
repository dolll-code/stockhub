<?php

namespace App\Models;

use App\Enums\StockStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $category_id
 * @property int $unit_id
 * @property ?int $main_supplier_id
 * @property float|string $purchase_price
 * @property float|string|null $selling_price
 * @property float|string $current_stock
 * @property float|string $min_stock
 * @property ?string $storage_location
 * @property ?string $description
 * @property ?string $photo_path
 * @property bool $is_active
 * @property ?int $created_by
 * @property ?int $updated_by
 * @property-read Category $category
 * @property-read Unit $unit
 * @property-read ?Supplier $mainSupplier
 * @property-read StockStatus $stock_status
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'unit_id',
        'main_supplier_id',
        'purchase_price',
        'selling_price',
        'min_stock',
        'storage_location',
        'description',
        'photo_path',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'current_stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function mainSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'main_supplier_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<StockInDetail, $this>
     */
    public function stockInDetails(): HasMany
    {
        return $this->hasMany(StockInDetail::class);
    }

    /**
     * @return HasMany<StockOutDetail, $this>
     */
    public function stockOutDetails(): HasMany
    {
        return $this->hasMany(StockOutDetail::class);
    }

    /**
     * @return HasMany<StockAdjustment, $this>
     */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Derived attribute for current stock status (BR-18).
     */
    public function getStockStatusAttribute(): StockStatus
    {
        return StockStatus::fromStock($this->current_stock, $this->min_stock);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('current_stock', '<=', 0);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeCriticalStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }
}
