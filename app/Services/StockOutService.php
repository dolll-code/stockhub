<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockOut;
use App\Models\StockOutDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockOutService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService,
        protected ActivityLogger $logger
    ) {}

    /**
     * Create a draft Stock Out transaction (FR-STO-01, BR-07).
     *
     * @param  array{transaction_date: string, purpose_type: string, destination: string, notes?: ?string, items: list<array{product_id: int, quantity: float|string}>}  $data
     */
    public function createDraft(array $data, User $actor, ?string $ip = null, ?string $userAgent = null): StockOut
    {
        return DB::transaction(function () use ($data, $actor, $ip, $userAgent) {
            $date = Carbon::parse($data['transaction_date']);
            $number = $this->documentNumberService->generateStockOutNumber($date);

            $stockOut = StockOut::create([
                'number' => $number,
                'transaction_date' => $data['transaction_date'],
                'purpose_type' => $data['purpose_type'],
                'destination' => trim($data['destination']),
                'notes' => $data['notes'] ?? null,
                'status' => TransactionStatus::DRAFT,
                'created_by' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                StockOutDetail::create([
                    'stock_out_id' => $stockOut->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (string) $item['quantity'],
                ]);
            }

            $this->logger->log(
                action: 'stock_out.created',
                description: "Pengguna [{$actor->name}] membuat draft barang keluar [{$stockOut->number}]",
                subject: $stockOut,
                actor: $actor,
                newValues: [
                    'number' => $stockOut->number,
                    'item_count' => count($data['items']),
                    'destination' => $stockOut->destination,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $stockOut;
        });
    }

    /**
     * Update an existing draft Stock Out transaction (FR-STO-02).
     *
     * @param  array{transaction_date: string, purpose_type: string, destination: string, notes?: ?string, items: list<array{product_id: int, quantity: float|string}>}  $data
     *
     * @throws InvalidStateTransitionException
     */
    public function updateDraft(StockOut $stockOut, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): StockOut
    {
        if ($stockOut->status !== TransactionStatus::DRAFT) {
            throw new InvalidStateTransitionException('Hanya transaksi berstatus draft yang dapat diubah.');
        }

        return DB::transaction(function () use ($stockOut, $data, $actor, $ip, $userAgent) {
            $stockOut->update([
                'transaction_date' => $data['transaction_date'],
                'purpose_type' => $data['purpose_type'],
                'destination' => trim($data['destination']),
                'notes' => $data['notes'] ?? null,
            ]);

            // Re-sync items
            $stockOut->details()->delete();

            foreach ($data['items'] as $item) {
                StockOutDetail::create([
                    'stock_out_id' => $stockOut->id,
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (string) $item['quantity'],
                ]);
            }

            $this->logger->log(
                action: 'stock_out.updated',
                description: "Pengguna [{$actor->name}] memperbarui draft barang keluar [{$stockOut->number}]",
                subject: $stockOut,
                actor: $actor,
                newValues: [
                    'number' => $stockOut->number,
                    'item_count' => count($data['items']),
                    'destination' => $stockOut->destination,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $stockOut;
        });
    }
}
