<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\LockTimeoutException;
use App\Exceptions\NoDifferenceException;
use App\Exceptions\ProductInactiveException;
use App\Exceptions\ReversalInsufficientStockException;
use App\Exceptions\StockChangedException;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockAdjustment;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\StockOut;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    public function __construct(
        protected ActivityLogger $logger,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Record an opening balance stock movement and set initial current_stock (BR-06, BR-17).
     * This primitive is the single authorized writer to `products.current_stock`.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function recordOpeningBalance(
        Product $product,
        float|string $openingStock,
        User $actor,
        ?string $notes = 'Saldo awal registrasi barang'
    ): ?StockMovement {
        $qty = (string) $openingStock;

        // BR-17: If opening_stock = 0, do not create movement
        if (bccomp($qty, '0', 3) <= 0) {
            return null;
        }

        return $this->runWithDeadlockRetry(function () use ($product, $qty, $actor, $notes): StockMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::with('unit')->where('id', $product->id)->lockForUpdate()->firstOrFail();

            // Validate decimal rules per unit (BR-01)
            $unit = $lockedProduct->unit;
            if (! $unit->allow_decimal) {
                if (bccomp($qty, (string) (int) (float) $qty, 3) !== 0) {
                    throw new InvalidArgumentException("Satuan {$unit->name} tidak mengizinkan nilai desimal.");
                }
            } else {
                $parts = explode('.', $qty);
                if (isset($parts[1]) && strlen($parts[1]) > 3) {
                    throw new InvalidArgumentException('Kuantitas stok awal maksimal memiliki 3 angka di belakang koma.');
                }
            }

            // Invariant checks: opening balance is only allowed once on initial registration
            if (bccomp((string) $lockedProduct->current_stock, '0', 3) !== 0) {
                throw new RuntimeException('Saldo awal hanya dapat dicatat saat registrasi barang dengan saldo nol.');
            }

            if ($lockedProduct->stockMovements()->exists()) {
                throw new RuntimeException('Barang sudah memiliki mutasi stok; saldo awal tidak dapat dicatat.');
            }

            $before = '0.000';
            $change = $qty;
            $after = $qty;

            // 1. Create exactly one immutable stock movement
            $movement = StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_type' => MovementType::OPENING_BALANCE,
                'reference_type' => null,
                'reference_id' => null,
                'reference_number' => null,
                'movement_date' => now()->toDateString(),
                'qty_before' => $before,
                'qty_change' => $change,
                'qty_after' => $after,
                'user_id' => $actor->id,
                'notes' => $notes ?: 'Saldo awal registrasi barang',
            ]);

            // 2. Set products.current_stock (BR-06: StockService is sole writer)
            $lockedProduct->forceFill([
                'current_stock' => $after,
            ])->save();

            $product->current_stock = $after;

            return $movement;
        });
    }

    /**
     * Confirm a Stock In draft document (FR-STI-03, BR-08, BR-09).
     *
     * @throws InvalidStateTransitionException|ProductInactiveException|ValidationException|LockTimeoutException
     */
    public function confirmStockIn(
        int|StockIn $stockIn,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockIn {
        $stockInId = $stockIn instanceof StockIn ? $stockIn->id : $stockIn;

        return $this->runWithDeadlockRetry(function () use ($stockInId, $actor, $ip, $userAgent): StockIn {
            // 1. Lock header FOR UPDATE first
            /** @var StockIn $lockedStockIn */
            $lockedStockIn = StockIn::with(['details.product.unit', 'supplier'])
                ->where('id', $stockInId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStockIn->status !== TransactionStatus::DRAFT) {
                throw new InvalidStateTransitionException('Transaksi ini sudah diproses oleh pengguna lain atau berstatus berbeda. Muat ulang halaman.');
            }

            if ($lockedStockIn->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Transaksi barang masuk harus memiliki minimal 1 item barang.'],
                ]);
            }

            // Validate transaction date within range
            $this->validateBusinessDate($lockedStockIn->transaction_date->toDateString(), 'transaction_date');

            // Supplier must be active if source_type == purchase (BR-22)
            if ($lockedStockIn->source_type === StockInSource::PURCHASE) {
                if (! $lockedStockIn->supplier || ! $lockedStockIn->supplier->is_active) {
                    throw ValidationException::withMessages([
                        'supplier_id' => ['Supplier transaksi pembelian tidak valid atau sudah nonaktif.'],
                    ]);
                }
            }

            // 2. Lock products in ASCENDING ID order (BR-09 deadlock prevention)
            $productIds = $lockedStockIn->details->pluck('product_id')->unique()->sort()->values()->all();
            $lockedProducts = Product::with('unit')
                ->whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 3. Validate each item
            $inactiveErrors = [];
            $validationErrors = [];

            foreach ($lockedStockIn->details as $index => $detail) {
                $product = $lockedProducts->get($detail->product_id);
                if (! $product || ! $product->is_active) {
                    $code = $product ? $product->code : "ID: {$detail->product_id}";
                    $inactiveErrors["items.{$index}.product_id"] = ["Barang {$code} sudah nonaktif. Hapus dari daftar atau aktifkan kembali."];

                    continue;
                }

                $qty = (string) $detail->quantity;
                if (bccomp($qty, '0', 3) <= 0) {
                    $validationErrors["items.{$index}.quantity"] = ['Kuantitas barang harus lebih besar dari 0.'];

                    continue;
                }

                $unit = $product->unit;
                if (! $unit->allow_decimal) {
                    if (bccomp($qty, (string) (int) (float) $qty, 3) !== 0) {
                        $validationErrors["items.{$index}.quantity"] = ["Kuantitas barang {$product->name} harus bilangan bulat untuk satuan {$unit->name}."];
                    }
                } else {
                    $parts = explode('.', $qty);
                    if (isset($parts[1]) && strlen($parts[1]) > 3) {
                        $validationErrors["items.{$index}.quantity"] = ["Kuantitas barang {$product->name} maksimal 3 angka desimal."];
                    }
                }
            }

            if (! empty($inactiveErrors)) {
                throw new ProductInactiveException('Terdapat barang nonaktif dalam transaksi.', $inactiveErrors);
            }

            if (! empty($validationErrors)) {
                throw ValidationException::withMessages($validationErrors);
            }

            // 4. Update stock and record movements
            foreach ($lockedStockIn->details as $detail) {
                /** @var Product $product */
                $product = $lockedProducts->get($detail->product_id);
                $before = (string) $product->current_stock;
                $change = (string) $detail->quantity;
                $after = bcadd($before, $change, 3);

                // Movement insert
                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => MovementType::STOCK_IN,
                    'reference_type' => StockIn::class,
                    'reference_id' => $lockedStockIn->id,
                    'reference_number' => $lockedStockIn->number,
                    'movement_date' => $lockedStockIn->transaction_date->toDateString(),
                    'qty_before' => $before,
                    'qty_change' => $change,
                    'qty_after' => $after,
                    'user_id' => $actor->id,
                    'notes' => "Barang Masuk {$lockedStockIn->number}".($lockedStockIn->supplier ? " dari {$lockedStockIn->supplier->name}" : ''),
                ]);

                // Sole update of current_stock (BR-06)
                $product->forceFill(['current_stock' => $after])->save();
            }

            // 5. Update header status
            $lockedStockIn->status = TransactionStatus::CONFIRMED;
            $lockedStockIn->confirmed_by = $actor->id;
            $lockedStockIn->confirmed_at = now();
            $lockedStockIn->save();

            // 6. Record audit log
            $this->logger->log(
                action: 'stock_in.confirmed',
                description: "Pengguna [{$actor->name}] mengonfirmasi transaksi barang masuk [{$lockedStockIn->number}]",
                subject: $lockedStockIn,
                actor: $actor,
                newValues: [
                    'number' => $lockedStockIn->number,
                    'status' => 'confirmed',
                    'item_count' => $lockedStockIn->details->count(),
                    'total_amount' => (float) $lockedStockIn->total_amount,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $lockedStockIn;
        });
    }

    /**
     * Cancel a Stock In draft document (BR-14).
     *
     * @throws InvalidStateTransitionException|ValidationException|LockTimeoutException
     */
    public function cancelDraftStockIn(
        int|StockIn $stockIn,
        string $reason,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockIn {
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages([
                'cancel_reason' => ['Alasan pembatalan draft wajib diisi antara 10 hingga 255 karakter.'],
            ]);
        }

        $stockInId = $stockIn instanceof StockIn ? $stockIn->id : $stockIn;

        return $this->runWithDeadlockRetry(function () use ($stockInId, $reason, $actor, $ip, $userAgent): StockIn {
            /** @var StockIn $lockedStockIn */
            $lockedStockIn = StockIn::where('id', $stockInId)->lockForUpdate()->firstOrFail();

            if ($lockedStockIn->status !== TransactionStatus::DRAFT) {
                throw new InvalidStateTransitionException('Transaksi ini sudah diproses atau bukan berstatus draft.');
            }

            $lockedStockIn->status = TransactionStatus::CANCELLED;
            $lockedStockIn->cancelled_by = $actor->id;
            $lockedStockIn->cancelled_at = now();
            $lockedStockIn->cancel_reason = $reason;
            $lockedStockIn->save();

            $this->logger->log(
                action: 'stock_in.draft_cancelled',
                description: "Pengguna [{$actor->name}] membatalkan draft barang masuk [{$lockedStockIn->number}]",
                subject: $lockedStockIn,
                actor: $actor,
                newValues: [
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $lockedStockIn;
        });
    }

    /**
     * Cancel a confirmed Stock In transaction with reversal movements (BR-13).
     *
     * @throws InvalidStateTransitionException|ReversalInsufficientStockException|ValidationException|LockTimeoutException
     */
    public function cancelConfirmedStockIn(
        int|StockIn $stockIn,
        string $reason,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockIn {
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages([
                'cancel_reason' => ['Alasan pembatalan transaksi wajib diisi antara 10 hingga 255 karakter.'],
            ]);
        }

        $stockInId = $stockIn instanceof StockIn ? $stockIn->id : $stockIn;

        return $this->runWithDeadlockRetry(function () use ($stockInId, $reason, $actor, $ip, $userAgent): StockIn {
            /** @var StockIn $lockedStockIn */
            $lockedStockIn = StockIn::with('details.product.unit')
                ->where('id', $stockInId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStockIn->status !== TransactionStatus::CONFIRMED) {
                throw new InvalidStateTransitionException('Hanya transaksi berstatus confirmed yang dapat dibatalkan melalui jalur pembatalan ini.');
            }

            // Lock products in ascending order
            $productIds = $lockedStockIn->details->pluck('product_id')->unique()->sort()->values()->all();
            $lockedProducts = Product::with('unit')
                ->whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Check if current_stock >= detail->quantity for all lines
            $reversalErrors = [];
            foreach ($lockedStockIn->details as $index => $detail) {
                /** @var Product $product */
                $product = $lockedProducts->get($detail->product_id);
                $current = (string) $product->current_stock;
                $qtyToDeduct = (string) $detail->quantity;

                if (bccomp($current, $qtyToDeduct, 3) < 0) {
                    $reversalErrors["items.{$index}.quantity"] = [
                        "Tidak dapat dibatalkan: stok {$product->name} saat ini {$current}, sedangkan {$qtyToDeduct} harus dikurangi.",
                    ];
                }
            }

            if (! empty($reversalErrors)) {
                $firstMsg = array_values($reversalErrors)[0][0];
                throw new ReversalInsufficientStockException($firstMsg, $reversalErrors);
            }

            // Reversal execution
            foreach ($lockedStockIn->details as $detail) {
                /** @var Product $product */
                $product = $lockedProducts->get($detail->product_id);
                $before = (string) $product->current_stock;
                $qty = (string) $detail->quantity;
                $change = bcmul($qty, '-1', 3);
                $after = bcsub($before, $qty, 3);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => MovementType::STOCK_IN_CANCEL,
                    'reference_type' => StockIn::class,
                    'reference_id' => $lockedStockIn->id,
                    'reference_number' => $lockedStockIn->number,
                    'movement_date' => now()->toDateString(),
                    'qty_before' => $before,
                    'qty_change' => $change,
                    'qty_after' => $after,
                    'user_id' => $actor->id,
                    'notes' => "Pembatalan barang masuk {$lockedStockIn->number}: {$reason}",
                ]);

                $product->forceFill(['current_stock' => $after])->save();
            }

            $lockedStockIn->status = TransactionStatus::CANCELLED;
            $lockedStockIn->cancelled_by = $actor->id;
            $lockedStockIn->cancelled_at = now();
            $lockedStockIn->cancel_reason = $reason;
            $lockedStockIn->save();

            $this->logger->log(
                action: 'stock_in.confirmed_cancelled',
                description: "Pengguna [{$actor->name}] membatalkan transaksi confirmed barang masuk [{$lockedStockIn->number}]",
                subject: $lockedStockIn,
                actor: $actor,
                newValues: [
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $lockedStockIn;
        });
    }

    /**
     * Confirm a Stock Out draft document (FR-STO-03, BR-08, BR-09, BR-10).
     *
     * @throws InvalidStateTransitionException|ProductInactiveException|InsufficientStockException|ValidationException|LockTimeoutException
     */
    public function confirmStockOut(
        int|StockOut $stockOut,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockOut {
        $stockOutId = $stockOut instanceof StockOut ? $stockOut->id : $stockOut;

        return $this->runWithDeadlockRetry(function () use ($stockOutId, $actor, $ip, $userAgent): StockOut {
            // 1. Lock header FOR UPDATE first
            /** @var StockOut $lockedStockOut */
            $lockedStockOut = StockOut::with('details.product.unit')
                ->where('id', $stockOutId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStockOut->status !== TransactionStatus::DRAFT) {
                throw new InvalidStateTransitionException('Transaksi ini sudah diproses oleh pengguna lain atau berstatus berbeda. Muat ulang halaman.');
            }

            if ($lockedStockOut->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Transaksi barang keluar harus memiliki minimal 1 item barang.'],
                ]);
            }

            // Validate date
            $this->validateBusinessDate($lockedStockOut->transaction_date->toDateString(), 'transaction_date');

            // 2. Lock products in ASCENDING ID order (BR-09)
            $productIds = $lockedStockOut->details->pluck('product_id')->unique()->sort()->values()->all();
            $lockedProducts = Product::with('unit')
                ->whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 3. Validate items & stock sufficiency (BR-10)
            $inactiveErrors = [];
            $insufficientErrors = [];
            $validationErrors = [];
            $insufficientSummaries = [];

            foreach ($lockedStockOut->details as $index => $detail) {
                /** @var Product|null $product */
                $product = $lockedProducts->get($detail->product_id);
                if (! $product || ! $product->is_active) {
                    $code = $product ? $product->code : "ID: {$detail->product_id}";
                    $inactiveErrors["items.{$index}.product_id"] = ["Barang {$code} sudah nonaktif. Hapus dari daftar atau aktifkan kembali."];

                    continue;
                }

                $qty = (string) $detail->quantity;
                if (bccomp($qty, '0', 3) <= 0) {
                    $validationErrors["items.{$index}.quantity"] = ['Kuantitas barang keluar harus lebih besar dari 0.'];

                    continue;
                }

                $unit = $product->unit;
                if (! $unit->allow_decimal) {
                    if (bccomp($qty, (string) (int) (float) $qty, 3) !== 0) {
                        $validationErrors["items.{$index}.quantity"] = ["Kuantitas barang {$product->name} harus bilangan bulat untuk satuan {$unit->name}."];
                    }
                } else {
                    $parts = explode('.', $qty);
                    if (isset($parts[1]) && strlen($parts[1]) > 3) {
                        $validationErrors["items.{$index}.quantity"] = ["Kuantitas barang {$product->name} maksimal 3 angka desimal."];
                    }
                }

                // Check stock sufficiency
                $currentStock = (string) $product->current_stock;
                if (bccomp($currentStock, $qty, 3) < 0) {
                    $formattedCurrent = rtrim(rtrim(number_format((float) $currentStock, 3, ',', '.'), '0'), ',');
                    $formattedRequired = rtrim(rtrim(number_format((float) $qty, 3, ',', '.'), '0'), ',');
                    $msg = "Stok {$product->name} tersisa {$formattedCurrent} {$unit->symbol}, diminta {$formattedRequired} {$unit->symbol}.";
                    $insufficientErrors["items.{$index}.quantity"] = [$msg];
                    $insufficientSummaries[] = "{$product->name} (diminta {$formattedRequired}, tersedia {$formattedCurrent})";
                }
            }

            if (! empty($inactiveErrors)) {
                throw new ProductInactiveException('Terdapat barang nonaktif dalam transaksi barang keluar.', $inactiveErrors);
            }

            if (! empty($insufficientErrors)) {
                $summary = 'Persediaan tidak mencukupi untuk: '.implode('; ', $insufficientSummaries).'.';
                throw new InsufficientStockException($summary, $insufficientErrors);
            }

            if (! empty($validationErrors)) {
                throw ValidationException::withMessages($validationErrors);
            }

            // 4. Update stock and record movements
            foreach ($lockedStockOut->details as $detail) {
                /** @var Product $product */
                $product = $lockedProducts->get($detail->product_id);
                $before = (string) $product->current_stock;
                $qty = (string) $detail->quantity;
                $change = bcmul($qty, '-1', 3);
                $after = bcsub($before, $qty, 3);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => MovementType::STOCK_OUT,
                    'reference_type' => StockOut::class,
                    'reference_id' => $lockedStockOut->id,
                    'reference_number' => $lockedStockOut->number,
                    'movement_date' => $lockedStockOut->transaction_date->toDateString(),
                    'qty_before' => $before,
                    'qty_change' => $change,
                    'qty_after' => $after,
                    'user_id' => $actor->id,
                    'notes' => "Barang Keluar {$lockedStockOut->number} ke {$lockedStockOut->destination}",
                ]);

                $product->forceFill(['current_stock' => $after])->save();
            }

            // 5. Update header status
            $lockedStockOut->status = TransactionStatus::CONFIRMED;
            $lockedStockOut->confirmed_by = $actor->id;
            $lockedStockOut->confirmed_at = now();
            $lockedStockOut->save();

            // 6. Record audit log
            $this->logger->log(
                action: 'stock_out.confirmed',
                description: "Pengguna [{$actor->name}] mengonfirmasi transaksi barang keluar [{$lockedStockOut->number}]",
                subject: $lockedStockOut,
                actor: $actor,
                newValues: [
                    'number' => $lockedStockOut->number,
                    'status' => 'confirmed',
                    'item_count' => $lockedStockOut->details->count(),
                    'destination' => $lockedStockOut->destination,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $lockedStockOut;
        });
    }

    /**
     * Cancel a Stock Out draft document (BR-14).
     *
     * @throws InvalidStateTransitionException|ValidationException|LockTimeoutException
     */
    public function cancelDraftStockOut(
        int|StockOut $stockOut,
        string $reason,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockOut {
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages([
                'cancel_reason' => ['Alasan pembatalan draft wajib diisi antara 10 hingga 255 karakter.'],
            ]);
        }

        $stockOutId = $stockOut instanceof StockOut ? $stockOut->id : $stockOut;

        return $this->runWithDeadlockRetry(function () use ($stockOutId, $reason, $actor, $ip, $userAgent): StockOut {
            /** @var StockOut $lockedStockOut */
            $lockedStockOut = StockOut::where('id', $stockOutId)->lockForUpdate()->firstOrFail();

            if ($lockedStockOut->status !== TransactionStatus::DRAFT) {
                throw new InvalidStateTransitionException('Transaksi ini sudah diproses atau bukan berstatus draft.');
            }

            $lockedStockOut->status = TransactionStatus::CANCELLED;
            $lockedStockOut->cancelled_by = $actor->id;
            $lockedStockOut->cancelled_at = now();
            $lockedStockOut->cancel_reason = $reason;
            $lockedStockOut->save();

            $this->logger->log(
                action: 'stock_out.draft_cancelled',
                description: "Pengguna [{$actor->name}] membatalkan draft barang keluar [{$lockedStockOut->number}]",
                subject: $lockedStockOut,
                actor: $actor,
                newValues: [
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $lockedStockOut;
        });
    }

    /**
     * Cancel a confirmed Stock Out transaction with reversal movements (BR-13).
     *
     * @throws InvalidStateTransitionException|ValidationException|LockTimeoutException
     */
    public function cancelConfirmedStockOut(
        int|StockOut $stockOut,
        string $reason,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockOut {
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 255) {
            throw ValidationException::withMessages([
                'cancel_reason' => ['Alasan pembatalan transaksi wajib diisi antara 10 hingga 255 karakter.'],
            ]);
        }

        $stockOutId = $stockOut instanceof StockOut ? $stockOut->id : $stockOut;

        return $this->runWithDeadlockRetry(function () use ($stockOutId, $reason, $actor, $ip, $userAgent): StockOut {
            /** @var StockOut $lockedStockOut */
            $lockedStockOut = StockOut::with('details.product.unit')
                ->where('id', $stockOutId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedStockOut->status !== TransactionStatus::CONFIRMED) {
                throw new InvalidStateTransitionException('Hanya transaksi berstatus confirmed yang dapat dibatalkan melalui jalur pembatalan ini.');
            }

            $productIds = $lockedStockOut->details->pluck('product_id')->unique()->sort()->values()->all();
            $lockedProducts = Product::with('unit')
                ->whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Reversal execution: stock increases back
            foreach ($lockedStockOut->details as $detail) {
                /** @var Product $product */
                $product = $lockedProducts->get($detail->product_id);
                $before = (string) $product->current_stock;
                $qty = (string) $detail->quantity;
                $change = $qty;
                $after = bcadd($before, $qty, 3);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => MovementType::STOCK_OUT_CANCEL,
                    'reference_type' => StockOut::class,
                    'reference_id' => $lockedStockOut->id,
                    'reference_number' => $lockedStockOut->number,
                    'movement_date' => now()->toDateString(),
                    'qty_before' => $before,
                    'qty_change' => $change,
                    'qty_after' => $after,
                    'user_id' => $actor->id,
                    'notes' => "Pembatalan barang keluar {$lockedStockOut->number}: {$reason}",
                ]);

                $product->forceFill(['current_stock' => $after])->save();
            }

            $lockedStockOut->status = TransactionStatus::CANCELLED;
            $lockedStockOut->cancelled_by = $actor->id;
            $lockedStockOut->cancelled_at = now();
            $lockedStockOut->cancel_reason = $reason;
            $lockedStockOut->save();

            $this->logger->log(
                action: 'stock_out.confirmed_cancelled',
                description: "Pengguna [{$actor->name}] membatalkan transaksi confirmed barang keluar [{$lockedStockOut->number}]",
                subject: $lockedStockOut,
                actor: $actor,
                newValues: [
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $lockedStockOut;
        });
    }

    /**
     * Create a stock adjustment record (FR-ADJ-01, BR-16).
     *
     * @param  array{adjustment_date: string, product_id: int, expected_system_qty: float|string, physical_qty: float|string, reason_code: string, notes: string}  $data
     *
     * @throws ProductInactiveException|StockChangedException|NoDifferenceException|ValidationException|LockTimeoutException
     */
    public function createAdjustment(
        array $data,
        User $actor,
        ?string $ip = null,
        ?string $userAgent = null
    ): StockAdjustment {
        $this->validateBusinessDate($data['adjustment_date'], 'adjustment_date');

        return $this->runWithDeadlockRetry(function () use ($data, $actor, $ip, $userAgent): StockAdjustment {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::with('unit')
                ->where('id', $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedProduct->is_active) {
                throw new ProductInactiveException("Barang {$lockedProduct->code} ({$lockedProduct->name}) sudah nonaktif.");
            }

            $unit = $lockedProduct->unit;
            $physicalQty = (string) $data['physical_qty'];
            if (bccomp($physicalQty, '0', 3) < 0) {
                throw ValidationException::withMessages([
                    'physical_qty' => ['Stok fisik tidak boleh bernilai negatif.'],
                ]);
            }

            if (! $unit->allow_decimal) {
                if (bccomp($physicalQty, (string) (int) (float) $physicalQty, 3) !== 0) {
                    throw ValidationException::withMessages([
                        'physical_qty' => ["Stok fisik harus bilangan bulat untuk satuan {$unit->name}."],
                    ]);
                }
            } else {
                $parts = explode('.', $physicalQty);
                if (isset($parts[1]) && strlen($parts[1]) > 3) {
                    throw ValidationException::withMessages([
                        'physical_qty' => ['Stok fisik maksimal memiliki 3 angka di belakang koma.'],
                    ]);
                }
            }

            // Check if system stock changed while form was open (AC-ADJ-03, D-33)
            $expected = (string) $data['expected_system_qty'];
            $lockedSystemQty = (string) $lockedProduct->current_stock;
            if (bccomp($expected, $lockedSystemQty, 3) !== 0) {
                $formattedCurrent = rtrim(rtrim(number_format((float) $lockedSystemQty, 3, ',', '.'), '0'), ',');
                throw new StockChangedException("Stok sistem berubah menjadi {$formattedCurrent}. Periksa kembali sebelum menyimpan.", (float) $lockedSystemQty);
            }

            // Calculate difference (AC-ADJ-04, physical_qty - system_qty)
            $difference = bcsub($physicalQty, $lockedSystemQty, 3);
            if (bccomp($difference, '0', 3) === 0) {
                throw new NoDifferenceException;
            }

            $adjustmentNumber = $this->documentNumberService->generateAdjustmentNumber(
                Carbon::parse($data['adjustment_date'])
            );

            // Create stock_adjustments row (final, no draft)
            $adjustment = StockAdjustment::create([
                'number' => $adjustmentNumber,
                'adjustment_date' => $data['adjustment_date'],
                'product_id' => $lockedProduct->id,
                'system_qty' => $lockedSystemQty,
                'physical_qty' => $physicalQty,
                'difference' => $difference,
                'reason_code' => $data['reason_code'],
                'notes' => trim($data['notes']),
                'created_by' => $actor->id,
            ]);

            // Create movement
            StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_type' => MovementType::ADJUSTMENT,
                'reference_type' => StockAdjustment::class,
                'reference_id' => $adjustment->id,
                'reference_number' => $adjustment->number,
                'movement_date' => $data['adjustment_date'],
                'qty_before' => $lockedSystemQty,
                'qty_change' => $difference,
                'qty_after' => $physicalQty,
                'user_id' => $actor->id,
                'notes' => "Penyesuaian stok: {$adjustment->reason_code->label()} ({$adjustment->notes})",
            ]);

            // Update product current_stock
            $lockedProduct->forceFill(['current_stock' => $physicalQty])->save();

            // Log activity
            $this->logger->log(
                action: 'adjustment.created',
                description: "Pengguna [{$actor->name}] membuat penyesuaian stok [{$adjustment->number}] untuk barang [{$lockedProduct->name}] ({$difference})",
                subject: $adjustment,
                actor: $actor,
                newValues: [
                    'number' => $adjustment->number,
                    'product_id' => $lockedProduct->id,
                    'system_qty' => (float) $lockedSystemQty,
                    'physical_qty' => (float) $physicalQty,
                    'difference' => (float) $difference,
                    'reason_code' => $adjustment->reason_code->value,
                ],
                ipAddress: $ip,
                userAgent: $userAgent
            );

            return $adjustment;
        });
    }

    /**
     * Validate business date according to backdate settings (BR-20).
     *
     * @throws ValidationException
     */
    protected function validateBusinessDate(string $dateStr, string $field): void
    {
        $date = Carbon::parse($dateStr)->startOfDay();
        $today = now()->startOfDay();

        if ($date->greaterThan($today)) {
            throw ValidationException::withMessages([
                $field => ['Tanggal transaksi tidak boleh lebih dari hari ini.'],
            ]);
        }

        $maxDays = (int) Setting::getValue('max_backdate_days', '7');
        $oldestAllowed = now()->subDays($maxDays)->startOfDay();

        if ($date->lessThan($oldestAllowed)) {
            throw ValidationException::withMessages([
                $field => ["Tanggal transaksi melebihi batas tanggal lampau yang diizinkan (maksimal {$maxDays} hari yang lalu)."],
            ]);
        }
    }

    /**
     * Execute callback within DB transaction with up to 3 deadlock retries (BR-09).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws LockTimeoutException
     */
    protected function runWithDeadlockRetry(callable $callback): mixed
    {
        try {
            return DB::transaction($callback, attempts: 3);
        } catch (QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;

            if ($driverCode === 1213 || $driverCode === 1205 || $sqlState === '40001') {
                throw new LockTimeoutException;
            }

            throw $e;
        }
    }
}
