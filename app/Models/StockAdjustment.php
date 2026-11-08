<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use Carbon\CarbonInterface;
use Database\Factories\StockAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $number
 * @property CarbonInterface $adjustment_date
 * @property int $product_id
 * @property float|string $system_qty
 * @property float|string $physical_qty
 * @property float|string $difference
 * @property AdjustmentReason $reason_code
 * @property string $notes
 * @property int $created_by
 */
class StockAdjustment extends Model
{
    /** @use HasFactory<StockAdjustmentFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'adjustment_date',
        'product_id',
        'system_qty',
        'physical_qty',
        'difference',
        'reason_code',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'system_qty' => 'decimal:3',
            'physical_qty' => 'decimal:3',
            'difference' => 'decimal:3',
            'reason_code' => AdjustmentReason::class,
        ];
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
