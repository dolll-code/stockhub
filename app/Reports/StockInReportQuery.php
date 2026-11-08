<?php

namespace App\Reports;

use App\Enums\TransactionStatus;
use App\Models\StockInDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockInReportQuery
{
    /**
     * @param  array{date_from?: ?string, date_to?: ?string, product_id?: ?int, category_id?: ?int, supplier_id?: ?int, source_type?: ?string}  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Build the query for confirmed stock in details.
     *
     * @return Builder<StockInDetail>
     */
    public function getBuilder(): Builder
    {
        $query = StockInDetail::query()
            ->with(['stockIn.supplier', 'stockIn.creator', 'product.unit', 'product.category'])
            ->join('stock_ins', 'stock_in_details.stock_in_id', '=', 'stock_ins.id')
            ->join('products', 'stock_in_details.product_id', '=', 'products.id')
            ->where('stock_ins.status', TransactionStatus::CONFIRMED->value) // BR-21: Only confirmed transactions
            ->select('stock_in_details.*');

        // Date range
        if (! empty($this->filters['date_from'])) {
            $query->whereDate('stock_ins.transaction_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('stock_ins.transaction_date', '<=', $this->filters['date_to']);
        }

        // Product
        if (! empty($this->filters['product_id'])) {
            $query->where('stock_in_details.product_id', (int) $this->filters['product_id']);
        }

        // Category
        if (! empty($this->filters['category_id'])) {
            $query->where('products.category_id', (int) $this->filters['category_id']);
        }

        // Supplier
        if (! empty($this->filters['supplier_id'])) {
            $query->where('stock_ins.supplier_id', (int) $this->filters['supplier_id']);
        }

        // Source Type
        if (! empty($this->filters['source_type'])) {
            $query->where('stock_ins.source_type', $this->filters['source_type']);
        }

        return $query->orderBy('stock_ins.transaction_date', 'desc')
            ->orderBy('stock_ins.id', 'desc');
    }

    /**
     * @return LengthAwarePaginator<int, StockInDetail>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, StockInDetail>
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
