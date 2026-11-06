<?php

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Enums\StockInSource;
use App\Enums\StockOutPurpose;
use App\Enums\StockStatus;
use App\Enums\TransactionStatus;

test('stock status calculates properly according to BR-18', function () {
    // min_stock = 5
    expect(StockStatus::fromStock(10, 5))->toBe(StockStatus::AMAN);
    expect(StockStatus::fromStock(5.001, 5))->toBe(StockStatus::AMAN);
    expect(StockStatus::fromStock(5, 5))->toBe(StockStatus::MINIMUM);
    expect(StockStatus::fromStock(3, 5))->toBe(StockStatus::MINIMUM);
    expect(StockStatus::fromStock(0.001, 5))->toBe(StockStatus::MINIMUM);
    expect(StockStatus::fromStock(0, 5))->toBe(StockStatus::HABIS);
    expect(StockStatus::fromStock(-1, 5))->toBe(StockStatus::HABIS);

    // min_stock = 0
    expect(StockStatus::fromStock(0, 0))->toBe(StockStatus::HABIS);
    expect(StockStatus::fromStock(1, 0))->toBe(StockStatus::AMAN);
});

test('enums return correct labels in Indonesian', function () {
    expect(TransactionStatus::DRAFT->label())->toBe('Draft');
    expect(TransactionStatus::CONFIRMED->label())->toBe('Terkonfirmasi');
    expect(TransactionStatus::CANCELLED->label())->toBe('Dibatalkan');

    expect(MovementType::OPENING_BALANCE->label())->toBe('Saldo Awal');
    expect(MovementType::STOCK_IN->label())->toBe('Barang Masuk');
    expect(MovementType::STOCK_OUT->label())->toBe('Barang Keluar');
    expect(MovementType::ADJUSTMENT->label())->toBe('Penyesuaian Stok');

    expect(StockInSource::PURCHASE->label())->toBe('Pembelian');
    expect(StockInSource::INTERNAL->label())->toBe('Internal');

    expect(StockOutPurpose::USAGE->label())->toBe('Pemakaian Internal');
    expect(StockOutPurpose::SALE->label())->toBe('Penjualan');

    expect(AdjustmentReason::DAMAGED->label())->toBe('Barang Rusak');
    expect(AdjustmentReason::COUNT_ERROR->label())->toBe('Kesalahan Perhitungan Fisik');
});
