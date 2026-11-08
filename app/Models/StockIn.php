<?php

namespace App\Models;

use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use Carbon\CarbonInterface;
use Database\Factories\StockInFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $number
 * @property CarbonInterface $transaction_date
 * @property StockInSource $source_type
 * @property ?int $supplier_id
 * @property ?string $reference_document
 * @property ?string $notes
 * @property TransactionStatus $status
 * @property float|string $total_amount
 * @property int $created_by
 * @property ?int $confirmed_by
 * @property ?int $cancelled_by
 * @property ?CarbonInterface $confirmed_at
 * @property ?CarbonInterface $cancelled_at
 * @property ?string $cancel_reason
 */
class StockIn extends Model
{
    /** @use HasFactory<StockInFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'transaction_date',
        'source_type',
        'supplier_id',
        'reference_document',
        'notes',
        'status',
        'total_amount',
        'created_by',
        'confirmed_by',
        'cancelled_by',
        'confirmed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'source_type' => StockInSource::class,
            'status' => TransactionStatus::class,
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<StockInDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(StockInDetail::class);
    }

    /**
     * @return HasManyThrough<Product, StockInDetail, $this>
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, StockInDetail::class, 'stock_in_id', 'id', 'id', 'product_id');
    }

    /**
     * @param  Builder<StockIn>  $query
     * @return Builder<StockIn>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::CONFIRMED->value);
    }

    /**
     * @param  Builder<StockIn>  $query
     * @return Builder<StockIn>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::DRAFT->value);
    }

    /**
     * @param  Builder<StockIn>  $query
     * @return Builder<StockIn>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::CANCELLED->value);
    }
}
