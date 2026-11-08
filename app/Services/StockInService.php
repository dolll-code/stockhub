<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockInDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockInService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected ActivityLogger $logger
    ) {}

    /**
     * Create a draft Stock In transaction (FR-STI-01, BR-07).
     *
     * @param  array{transaction_date: string, source_type: string, supplier_id?: ?int, reference_document?: ?string, notes?: ?string, items: list<array{product_id: int, quantity: float|string, unit_price?: float|string|null}>}  $data
     */
    public function createDraft(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): StockIn
    {
        return DB::transaction(function () use ($data, $actor, $ip, $userAgent) {
            $date = Carbon::parse($data['transaction_date']);
            $number = $this->documentNumberService->generateStockInNumber($date);

            $stockIn = StockIn::create([
                'number' => $number,
                'transaction_date' => $data['transaction_date'],
                'source_type' => $data['source_type'],
                'supplier_id' => ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                'reference_document' => $data['reference_document'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => TransactionStatus::DRAFT,
                'total_amount' => 0.00,
                'created_by' => $actor->id,
            ]);

            $productIds = collect($data['items'])->pluck('product_id')->all();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $totalAmount = '0.00';
            $canViewPrice = $actor->can('products.view-price');

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = (string) $item['quantity'];

                // Unit price determination (BR-26: SG uses product purchase_price automatically)
                if ($canViewPrice && isset($item['unit_price']) && $item['unit_price'] !== '') {
                    $unitPrice = (string) $item['unit_price'];
                } else {
                    $unitPrice = (string) $product->purchase_price;
                }

                $subtotal = bcmul($quantity, $unitPrice, 2);
                $totalAmount = bcadd($totalAmount, $subtotal, 2);

                StockInDetail::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            $stockIn->forceFill(['total_amount' => $totalAmount])->save();

            $this->logger->log(
                action: 'stock_in.created',
                description: "Pengguna [{$actor->name}] membuat draft barang masuk [{$stockIn->number}]",
                subject: $stockIn,
                actor: $actor,
                newValues: [
                    'number' => $stockIn->number,
                    'item_count' => count($data['items']),
                    'total_amount' => (float) $totalAmount,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $stockIn;
        });
    }

    /**
     * Update an existing draft Stock In transaction (FR-STI-02).
     *
     * @param  array{transaction_date: string, source_type: string, supplier_id?: ?int, reference_document?: ?string, notes?: ?string, items: list<array{product_id: int, quantity: float|string, unit_price?: float|string|null}>}  $data
     *
     * @throws InvalidStateTransitionException
     */
    public function updateDraft(StockIn $stockIn, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): StockIn
    {
        if ($stockIn->status !== TransactionStatus::DRAFT) {
            throw new InvalidStateTransitionException('Hanya transaksi berstatus draft yang dapat diubah.');
        }

        return DB::transaction(function () use ($stockIn, $data, $actor, $ip, $userAgent) {
            $stockIn->update([
                'transaction_date' => $data['transaction_date'],
                'source_type' => $data['source_type'],
                'supplier_id' => ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                'reference_document' => $data['reference_document'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Re-sync items
            $stockIn->details()->delete();

            $productIds = collect($data['items'])->pluck('product_id')->all();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $totalAmount = '0.00';
            $canViewPrice = $actor->can('products.view-price');

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);
                $quantity = (string) $item['quantity'];

                if ($canViewPrice && isset($item['unit_price']) && $item['unit_price'] !== '') {
                    $unitPrice = (string) $item['unit_price'];
                } else {
                    $unitPrice = (string) $product->purchase_price;
                }

                $subtotal = bcmul($quantity, $unitPrice, 2);
                $totalAmount = bcadd($totalAmount, $subtotal, 2);

                StockInDetail::create([
                    'stock_in_id' => $stockIn->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            $stockIn->forceFill(['total_amount' => $totalAmount])->save();

            $this->logger->log(
                action: 'stock_in.updated',
                description: "Pengguna [{$actor->name}] memperbarui draft barang masuk [{$stockIn->number}]",
                subject: $stockIn,
                actor: $actor,
                newValues: [
                    'number' => $stockIn->number,
                    'item_count' => count($data['items']),
                    'total_amount' => (float) $totalAmount,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $stockIn;
        });
    }
}
