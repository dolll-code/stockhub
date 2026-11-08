<?php

namespace App\Reports;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockReportQuery
{
    /**
     * @param  array{category_id?: ?int, product_id?: ?int, status?: ?string, stock_status?: ?string, as_of_date?: ?string}  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Build the underlying Eloquent query.
     *
     * @return Builder<Product>
     */
    public function getBuilder(): Builder
    {
        $asOfDate = ! empty($this->filters['as_of_date'])
            ? Carbon::parse($this->filters['as_of_date'])->toDateString()
            : null;

        $isPastDate = $asOfDate !== null && $asOfDate < now()->toDateString();

        $query = Product::with(['category', 'unit']);

        if ($isPastDate) {
            // As-of stock calculation: sum of movements up to as_of_date (FR-RPT-01, AC-RPT-04)
            $query->select('products.*')
                ->selectSub(function ($sub) use ($asOfDate) {
                    $sub->from('stock_movements')
                        ->whereColumn('stock_movements.product_id', 'products.id')
                        ->where('stock_movements.movement_date', '<=', $asOfDate)
                        ->selectRaw('COALESCE(SUM(qty_change), 0)');
                }, 'as_of_stock');
        }

        // Filter: Category
        if (! empty($this->filters['category_id'])) {
            $query->where('products.category_id', (int) $this->filters['category_id']);
        }

        // Filter: Product
        if (! empty($this->filters['product_id'])) {
            $query->where('products.id', (int) $this->filters['product_id']);
        }

        // Filter: Active / Inactive status
        if (! empty($this->filters['status'])) {
            if ($this->filters['status'] === 'active') {
                $query->where('products.is_active', true);
            } elseif ($this->filters['status'] === 'inactive') {
                $query->where('products.is_active', false);
            }
        }

        // Filter: Stock status (aman, minimum, habis)
        if (! empty($this->filters['stock_status'])) {
            $statusVal = $this->filters['stock_status'];
            $stockColumn = $isPastDate ? 'as_of_stock' : 'current_stock';

            if ($isPastDate) {
                if ($statusVal === 'habis') {
                    $query->havingRaw('as_of_stock <= 0');
                } elseif ($statusVal === 'minimum') {
                    $query->havingRaw('as_of_stock > 0 AND as_of_stock <= products.min_stock');
                } elseif ($statusVal === 'aman') {
                    $query->havingRaw('as_of_stock > products.min_stock');
                }
            } else {
                if ($statusVal === 'habis') {
                    $query->where('current_stock', '<=', 0);
                } elseif ($statusVal === 'minimum') {
                    $query->where('current_stock', '>', 0)
                        ->whereColumn('current_stock', '<=', 'min_stock');
                } elseif ($statusVal === 'aman') {
                    $query->whereColumn('current_stock', '>', 'min_stock');
                }
            }
        }

        return $query->orderBy('products.name', 'asc');
    }

    /**
     * Get paginated results for web view.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage)->withQueryString();
    }

    /**
     * Get all matching records for export (BR-31).
     *
     * @return Collection<int, Product>
     */
    public function get(): Collection
    {
        return $this->getBuilder()->get();
    }

    /**
     * Count matching records.
     */
    public function count(): int
    {
        return $this->getBuilder()->count();
    }
}
