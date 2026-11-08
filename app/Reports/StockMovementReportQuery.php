<?php

namespace App\Reports;

use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockMovementReportQuery
{
    /**
     * @param  array{date_from?: ?string, date_to?: ?string, product_id?: ?int, category_id?: ?int, movement_type?: ?string}  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Build the query for stock movements (includes original and reversal per AC-RPT-02).
     *
     * @return Builder<StockMovement>
     */
    public function getBuilder(): Builder
    {
        $query = StockMovement::query()
            ->with(['product.unit', 'product.category', 'user'])
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->select('stock_movements.*');

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('stock_movements.movement_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('stock_movements.movement_date', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['product_id'])) {
            $query->where('stock_movements.product_id', (int) $this->filters['product_id']);
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('products.category_id', (int) $this->filters['category_id']);
        }

        if (! empty($this->filters['movement_type'])) {
            $query->where('stock_movements.movement_type', $this->filters['movement_type']);
        }

        return $query->orderBy('stock_movements.movement_date', 'desc')
            ->orderBy('stock_movements.id', 'desc');
    }

    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, StockMovement>
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
