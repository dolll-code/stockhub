<?php

namespace App\Models;

use App\Enums\StockOutPurpose;
use App\Enums\TransactionStatus;
use Database\Factories\StockOutFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class StockOut extends Model
{
    /** @use HasFactory<StockOutFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'transaction_date',
        'purpose_type',
        'destination',
        'notes',
        'status',
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
            'purpose_type' => StockOutPurpose::class,
            'status' => TransactionStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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
     * @return HasMany<StockOutDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(StockOutDetail::class);
    }

    /**
     * @return HasManyThrough<Product, StockOutDetail, $this>
     */
    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, StockOutDetail::class, 'stock_out_id', 'id', 'id', 'product_id');
    }

    /**
     * @param  Builder<StockOut>  $query
     * @return Builder<StockOut>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::CONFIRMED->value);
    }

    /**
     * @param  Builder<StockOut>  $query
     * @return Builder<StockOut>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::DRAFT->value);
    }

    /**
     * @param  Builder<StockOut>  $query
     * @return Builder<StockOut>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', TransactionStatus::CANCELLED->value);
    }
}
