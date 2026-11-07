<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate sequential code for a Supplier: SUP-NNNN (BR-05, BR-19).
     */
    public function generateSupplierCode(): string
    {
        return $this->generateNextNumber(
            prefix: 'SUP',
            period: null,
            existsCallback: fn (string $code): bool => Supplier::where('code', $code)->exists()
        );
    }

    /**
     * Generate sequential code for a Product: BRG-NNNN (BR-05, BR-19).
     */
    public function generateProductCode(): string
    {
        return $this->generateNextNumber(
            prefix: 'BRG',
            period: null,
            existsCallback: fn (string $code): bool => Product::where('code', $code)->exists()
        );
    }

    /**
     * Generic sequential document numbering engine with row-lock and retry protection.
     * Extensible for monthly period transactions (Phase 4: IN-YYYYMM-NNNN, OUT-YYYYMM-NNNN, ADJ-YYYYMM-NNNN).
     *
     * @param  (callable(string): bool)|null  $existsCallback
     */
    public function generateNextNumber(string $prefix, ?string $period = null, ?callable $existsCallback = null): string
    {
        $sequenceKey = $period ? "{$prefix}-{$period}" : $prefix;

        $runInLock = function () use ($prefix, $period, $sequenceKey, $existsCallback): string {
            // Ensure sequence row exists without race conditions
            DB::statement(
                'INSERT INTO document_sequences (sequence_key, last_number, created_at, updated_at) VALUES (?, 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE sequence_key = sequence_key',
                [$sequenceKey]
            );

            /** @var DocumentSequence $sequence */
            $sequence = DocumentSequence::where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->firstOrFail();

            do {
                $sequence->last_number++;
                $sequence->save();

                $numberStr = sprintf('%04d', $sequence->last_number);
                $code = $period ? "{$prefix}-{$period}-{$numberStr}" : "{$prefix}-{$numberStr}";
            } while ($existsCallback !== null && $existsCallback($code));

            return $code;
        };

        if (DB::transactionLevel() > 0) {
            return $runInLock();
        }

        return DB::transaction($runInLock);
    }
}
