<?php

namespace App\Reports;

use App\Models\StockAdjustment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockAdjustmentReportQuery
{
    /**
     * @param  array{date_from?: ?string, date_to?: ?string, product_id?: ?int, category_id?: ?int, reason_code?: ?string}  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Build the query for stock adjustments.
     *
     * @return Builder<StockAdjustment>
     */
    public function getBuilder(): Builder
    {
        $query = StockAdjustment::query()
            ->with(['product.unit', 'product.category', 'creator'])
            ->join('products', 'stock_adjustments.product_id', '=', 'products.id')
            ->select('stock_adjustments.*');

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('stock_adjustments.adjustment_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('stock_adjustments.adjustment_date', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['product_id'])) {
            $query->where('stock_adjustments.product_id', (int) $this->filters['product_id']);
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('products.category_id', (int) $this->filters['category_id']);
        }

        if (! empty($this->filters['reason_code'])) {
            $query->where('stock_adjustments.reason_code', $this->filters['reason_code']);
        }

        return $query->orderBy('stock_adjustments.adjustment_date', 'desc')
            ->orderBy('stock_adjustments.id', 'desc');
    }

    /**
     * @return LengthAwarePaginator<int, StockAdjustment>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, StockAdjustment>
     */
    public function get(): Collection
    {
        return $this->getBuilder()->get();
    }

    public function count(): int
    {
        return $this->getBuilder()->count();
    }
}
