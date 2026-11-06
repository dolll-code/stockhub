<?php

namespace App\Models;

use App\Enums\MovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'movement_date',
        'qty_before',
        'qty_change',
        'qty_after',
        'user_id',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'movement_type' => MovementType::class,
            'qty_before' => 'decimal:3',
            'qty_change' => 'decimal:3',
            'qty_after' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enforce append-only invariant per PRD BR-15.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('StockMovement is append-only and cannot be updated (BR-15).');
        });

        static::deleting(function () {
            throw new RuntimeException('StockMovement is append-only and cannot be deleted (BR-15).');
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(null, 'reference_type', 'reference_id');
    }
}
