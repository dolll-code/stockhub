<?php

namespace App\Reports;

use App\Enums\TransactionStatus;
use App\Models\StockOutDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockOutReportQuery
{
    /**
     * @param  array{date_from?: ?string, date_to?: ?string, product_id?: ?int, category_id?: ?int, purpose_type?: ?string}  $filters
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
     * Build the query for confirmed stock out details.
     *
     * @return Builder<StockOutDetail>
     */
    public function getBuilder(): Builder
    {
        $query = StockOutDetail::query()
            ->with(['stockOut.creator', 'product.unit', 'product.category'])
            ->join('stock_outs', 'stock_out_details.stock_out_id', '=', 'stock_outs.id')
            ->join('products', 'stock_out_details.product_id', '=', 'products.id')
            ->where('stock_outs.status', TransactionStatus::CONFIRMED->value) // BR-21: Only confirmed transactions
            ->select('stock_out_details.*');

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('stock_outs.transaction_date', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('stock_outs.transaction_date', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['product_id'])) {
            $query->where('stock_out_details.product_id', (int) $this->filters['product_id']);
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('products.category_id', (int) $this->filters['category_id']);
        }

        if (! empty($this->filters['purpose_type'])) {
            $query->where('stock_outs.purpose_type', $this->filters['purpose_type']);
        }

        return $query->orderBy('stock_outs.transaction_date', 'desc')
            ->orderBy('stock_outs.id', 'desc');
    }

    /**
     * @return LengthAwarePaginator<int, StockOutDetail>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBuilder()->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, StockOutDetail>
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
