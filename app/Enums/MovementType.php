<?php

namespace App\Enums;

enum MovementType: string
{
    case OPENING_BALANCE = 'opening_balance';
    case STOCK_IN = 'stock_in';
    case STOCK_OUT = 'stock_out';
    case ADJUSTMENT = 'adjustment';
    case STOCK_IN_CANCEL = 'stock_in_cancel';
    case STOCK_OUT_CANCEL = 'stock_out_cancel';

    public function label(): string
    {
        return match ($this) {
            self::OPENING_BALANCE => 'Saldo Awal',
            self::STOCK_IN => 'Barang Masuk',
            self::STOCK_OUT => 'Barang Keluar',
            self::ADJUSTMENT => 'Penyesuaian Stok',
            self::STOCK_IN_CANCEL => 'Pembatalan Barang Masuk',
            self::STOCK_OUT_CANCEL => 'Pembatalan Barang Keluar',
        };
    }

    public function isAddition(): bool
    {
        return match ($this) {
            self::OPENING_BALANCE, self::STOCK_IN, self::STOCK_OUT_CANCEL => true,
            self::STOCK_OUT, self::STOCK_IN_CANCEL => false,
            self::ADJUSTMENT => false, // Bertanda dinamis sesuai qty_change
        };
    }
}
